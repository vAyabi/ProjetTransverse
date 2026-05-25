<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('delete')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID de salarié invalide", 400);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Activer le mode d'erreur pour PDO
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $salarie = new Salarie($db);
    $salarie->id_salarie = intval($_GET["id"]);
    
    // Vérifier que le salarié existe
    if (!$salarie->findOne()) {
        ApiResponse::notFound("Salarié non trouvé");
        exit;
    }
    
    // Vérifier les références avant de supprimer
    $stmt = $db->prepare("
        SELECT 
            (SELECT COUNT(*) FROM inscriptions_evenements WHERE id_salarie = ?) as inscriptions,
            (SELECT COUNT(*) FROM rendez_vous_medicaux WHERE id_salarie = ?) as rdv,
            (SELECT COUNT(*) FROM signalements WHERE id_salarie = ?) as signalements,
            (SELECT COUNT(*) FROM communautes_membres WHERE id_salarie = ?) as communautes,
            (SELECT COUNT(*) FROM participations_associations WHERE id_salarie = ?) as associations
    ");
    
    $stmt->execute([
        $salarie->id_salarie, 
        $salarie->id_salarie, 
        $salarie->id_salarie,
        $salarie->id_salarie, 
        $salarie->id_salarie
    ]);
    
    $references = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log("Références pour salarié " . $salarie->id_salarie . ": " . json_encode($references));
    
    // Ajouter une raison d'archivage
    $salarie->raison_archivage = isset($_POST['raison']) ? $_POST['raison'] : "Suppression via API admin";
    
    // Essayer de supprimer (archiver) le salarié
    if ($salarie->delete()) {
        ApiResponse::success([
            'message' => "Salarié archivé avec succès",
            'id_salarie' => $salarie->id_salarie,
            'references_supprimees' => $references
        ], "Salarié archivé avec succès");
    } else {
        ApiResponse::error("Impossible de supprimer le salarié", 500);
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO lors de la suppression du salarié: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ApiResponse::error("Erreur base de données: " . $e->getMessage(), 500);
} catch (Exception $e) {
    error_log("Erreur lors de la suppression du salarié: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ApiResponse::error("Erreur: " . $e->getMessage(), 500);
}
?>
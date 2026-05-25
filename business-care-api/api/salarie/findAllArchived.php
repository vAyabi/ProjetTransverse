<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $salarie = new Salarie($db);
    
    // Récupérer tous les salariés archivés
    $stmt = $salarie->findAllArchived();
    $num = $stmt->rowCount();
    
    if ($num > 0) {
        $salaries_arr = array();
        $salaries_arr["salaries_archives"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $salarie_item = array(
                "id_archive" => $id_archive,
                "id_salarie_original" => $id_salarie_original,
                "id_entreprise_original" => $id_entreprise_original,
                "nom" => $nom,
                "email" => $email,
                "statut" => $statut,
                "date_archivage" => $date_archivage,
                "raison_archivage" => $raison_archivage,
                "nom_entreprise" => isset($nom_entreprise) ? $nom_entreprise : "Entreprise archivée"
            );
            
            array_push($salaries_arr["salaries_archives"], $salarie_item);
        }
        
        ApiResponse::success($salaries_arr);
    } else {
        ApiResponse::success(["salaries_archives" => []], "Aucun salarié archivé trouvé");
    }
    
} catch (Exception $e) {
    ApiResponse::error("Erreur: " . $e->getMessage(), 500);
}
?>
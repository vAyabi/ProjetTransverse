<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Gérer les requêtes OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    ApiResponse::error("Méthode non autorisée. Utilisez POST ou DELETE.", 405);
    exit;
}

// Récupérer les données JSON
$input = file_get_contents("php://input");
$data = json_decode($input);

// Vérifier que les données sont valides
if (!$data || !isset($data->id_salarie)) {
    ApiResponse::error("ID du salarié requis", 400);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $salarie = new Salarie($db);
    $salarie->id_salarie = intval($data->id_salarie);
    $salarie->raison_archivage = isset($data->raison_archivage) ? $data->raison_archivage : "Archivage manuel";
    
    // Vérifier que le salarié existe
    if (!$salarie->findOne()) {
        ApiResponse::error("Salarié non trouvé", 404);
        exit;
    }
    
    // Archiver le salarié
    if ($salarie->archiver()) {
        ApiResponse::success(["id_salarie" => $salarie->id_salarie], "Salarié archivé avec succès");
    } else {
        ApiResponse::error("Impossible d'archiver le salarié", 500);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans archive.php: " . $e->getMessage());
    ApiResponse::error("Erreur: " . $e->getMessage(), 500);
}
?>
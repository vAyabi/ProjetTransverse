<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Prestataire.php';
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
    ApiResponse::error("ID de prestataire invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$prestataire = new Prestataire($db);
$prestataire->id_prestataire = intval($_GET["id"]);

if (!$prestataire->findOne()) {
    ApiResponse::notFound("Prestataire non trouvé");
    exit;
}

if ($prestataire->delete()) {
    ApiResponse::success(null, "Prestataire supprimé avec succès");
} else {
    ApiResponse::error("Impossible de supprimer le prestataire", 500);
}
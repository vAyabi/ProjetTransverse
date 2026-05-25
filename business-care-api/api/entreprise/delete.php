<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
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
    ApiResponse::error("ID d'entreprise invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$entreprise->id_entreprise = intval($_GET["id"]);

if (!$entreprise->findOne()) {
    ApiResponse::notFound("Entreprise non trouvée");
    exit;
}

if ($entreprise->delete()) {
    ApiResponse::success(null, "Entreprise supprimée avec succès");
} else {
    ApiResponse::error("Impossible de supprimer l'entreprise", 500);
}

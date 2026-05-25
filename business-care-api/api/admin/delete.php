<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Admin.php';
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
    ApiResponse::error("ID d'administrateur invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->id_admin = intval($_GET["id"]);

if (!$admin->findOne()) {
    ApiResponse::notFound("Administrateur non trouvé");
    exit;
}

if ($admin->delete()) {
    ApiResponse::success(null, "Administrateur supprimé avec succès");
} else {
    ApiResponse::error("Impossible de supprimer l'administrateur", 500);
}
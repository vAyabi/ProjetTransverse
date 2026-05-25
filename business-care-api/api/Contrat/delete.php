<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Contrat.php';
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
    ApiResponse::error("ID de contrat invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$contrat = new Contrat($db);
$contrat->id_contrat = intval($_GET["id"]);

if (!$contrat->findOne()) {
    ApiResponse::notFound("Contrat non trouvé");
    exit;
}

if ($contrat->delete()) {
    ApiResponse::success(null, "Contrat supprimé avec succès");
} else {
    ApiResponse::error("Impossible de supprimer le contrat", 500);
}
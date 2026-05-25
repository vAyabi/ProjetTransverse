<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: DELETE, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('delete') && !methodIsAllowed('post')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (empty($data->id_entreprise)) {
    ApiResponse::error("ID de l'entreprise non fourni", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$entreprise->id_entreprise = $data->id_entreprise;
$entreprise->raison_archivage = $data->raison_archivage ?? "Entreprise supprimée";

try {
    if ($entreprise->archiver()) {
        ApiResponse::success(null, "Entreprise et ses salariés archivés avec succès");
    } else {
        ApiResponse::error("Impossible d'archiver l'entreprise", 500);
    }
} catch (Exception $e) {
    ApiResponse::error("Erreur lors de l'archivage: " . $e->getMessage(), 500);
}
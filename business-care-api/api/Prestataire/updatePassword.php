<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Prestataire.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('update')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->id_prestataire) ||
    empty($data->password)
) {
    ApiResponse::error("Données incomplètes. ID du prestataire et mot de passe sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$prestataire = new Prestataire($db);
$prestataire->id_prestataire = $data->id_prestataire;

if (!$prestataire->findOne()) {
    ApiResponse::notFound("Prestataire non trouvé");
    exit;
}

$prestataire->password = $data->password;

if ($prestataire->updatePassword()) {
    ApiResponse::success(null, "Mot de passe mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour le mot de passe", 500);
}
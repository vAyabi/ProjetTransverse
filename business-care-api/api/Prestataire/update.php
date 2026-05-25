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
    empty($data->nom) ||
    empty($data->email) ||
    empty($data->type_prestation)
) {
    ApiResponse::error("Données incomplètes. ID du prestataire, nom, email et type de prestation sont requis.", 400);
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

$prestataire->nom = $data->nom;
$prestataire->specialite = $data->specialite ?? $prestataire->specialite;
$prestataire->email = $data->email;
$prestataire->telephone = $data->telephone ?? $prestataire->telephone;
$prestataire->rib = $data->rib ?? $prestataire->rib;
$prestataire->type_prestation = $data->type_prestation;
$prestataire->tarif_horaire = $data->tarif_horaire ?? $prestataire->tarif_horaire;
$prestataire->statut_validation = $data->statut_validation ?? $prestataire->statut_validation;

if ($prestataire->update()) {
    ApiResponse::success(null, "Prestataire mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour le prestataire", 500);
}
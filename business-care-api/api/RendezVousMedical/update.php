<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/RendezVousMedical.php';
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
    empty($data->id_rdv) ||
    empty($data->date_heure) ||
    empty($data->type) ||
    empty($data->statut)
) {
    ApiResponse::error("Données incomplètes. ID du rendez-vous, date/heure, type et statut sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$rdv = new RendezVousMedical($db);
$rdv->id_rdv = $data->id_rdv;

if (!$rdv->findOne()) {
    ApiResponse::notFound("Rendez-vous non trouvé");
    exit;
}

$rdv->date_heure = $data->date_heure;
$rdv->type = $data->type;
$rdv->notes = $data->notes ?? $rdv->notes;
$rdv->statut = $data->statut;

if ($rdv->update()) {
    ApiResponse::success(null, "Rendez-vous mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour le rendez-vous", 500);
}
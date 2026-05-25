<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
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
    empty($data->id_salarie) ||
    empty($data->nom) ||
    empty($data->email)
) {
    ApiResponse::error("Données incomplètes. ID du salarié, nom et email sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$salarie = new Salarie($db);
$salarie->id_salarie = $data->id_salarie;

if (!$salarie->findOne()) {
    ApiResponse::notFound("Salarié non trouvé");
    exit;
}

$salarie->nom = $data->nom;
$salarie->email = $data->email;
$salarie->statut = $data->statut ?? $salarie->statut;
$salarie->first_login = $data->first_login ?? $salarie->first_login;

if ($salarie->update()) {
    ApiResponse::success(null, "Salarié mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour le salarié", 500);
}
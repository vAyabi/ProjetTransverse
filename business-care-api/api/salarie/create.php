<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('create')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->nom) ||
    empty($data->email) ||
    empty($data->password) ||
    empty($data->id_entreprise)
) {
    ApiResponse::error("Données incomplètes. Nom, email, mot de passe et ID de l'entreprise sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$salarie = new Salarie($db);
$salarie->email = $data->email;

if ($salarie->emailExists()) {
    ApiResponse::error("Un salarié avec cet email existe déjà.", 400);
    exit;
}

$salarie->nom = $data->nom;
$salarie->password = $data->password;
$salarie->statut = $data->statut ?? 1;
$salarie->first_login = $data->first_login ?? 1;
$salarie->id_entreprise = $data->id_entreprise;

if ($salarie->create()) {
    ApiResponse::success(
        array("id_salarie" => $db->lastInsertId()),
        "Salarié créé avec succès",
        201
    );
} else {
    ApiResponse::error("Impossible de créer le salarié", 500);
}
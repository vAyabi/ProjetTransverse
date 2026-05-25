<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Prestataire.php';
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
    empty($data->type_prestation)
) {
    ApiResponse::error("Données incomplètes. Nom, email, mot de passe et type de prestation sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$prestataire = new Prestataire($db);
$prestataire->email = $data->email;

if ($prestataire->emailExists()) {
    ApiResponse::error("Un prestataire avec cet email existe déjà.", 400);
    exit;
}

$prestataire->nom = $data->nom;
$prestataire->specialite = $data->specialite ?? null;
$prestataire->password = $data->password;
$prestataire->telephone = $data->telephone ?? null;
$prestataire->rib = $data->rib ?? null;
$prestataire->type_prestation = $data->type_prestation;
$prestataire->tarif_horaire = $data->tarif_horaire ?? null;
$prestataire->statut_validation = $data->statut_validation ?? 'en_attente';

if ($prestataire->create()) {
    ApiResponse::success(
        array("id_prestataire" => $db->lastInsertId()),
        "Prestataire créé avec succès. Votre compte est en attente de validation.",
        201
    );
} else {
    ApiResponse::error("Impossible de créer le prestataire", 500);
}
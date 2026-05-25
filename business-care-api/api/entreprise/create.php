<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
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
    empty($data->type_formule)
) {
    ApiResponse::error("Données incomplètes. Nom, email, mot de passe et type de formule sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$entreprise->email = $data->email;

if ($entreprise->emailExists()) {
    ApiResponse::error("Une entreprise avec cet email existe déjà.", 400);
    exit;
}

$entreprise->nom = $data->nom;
$entreprise->siret = $data->siret ?? null;
$entreprise->password = $data->password;
$entreprise->telephone = $data->telephone ?? null;
$entreprise->adresse = $data->adresse ?? null;
$entreprise->type_formule = $data->type_formule;
$entreprise->statut = $data->statut ?? 1;

if ($entreprise->create()) {
    ApiResponse::success(
        array("id_entreprise" => $db->lastInsertId()),
        "Entreprise créée avec succès",
        201
    );
} else {
    ApiResponse::error("Impossible de créer l'entreprise", 500);
}
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
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


if (empty($data->id_entreprise)) {
    ApiResponse::error("ID d'entreprise manquant", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$entreprise->id_entreprise = $data->id_entreprise;


if (!$entreprise->findOne()) {
    ApiResponse::notFound("Entreprise non trouvée");
    exit;
}


$updateStatusOnly = isset($data->statut) && count((array)$data) === 2;

if (!$updateStatusOnly) {
   
    if (empty($data->nom) || empty($data->email) || empty($data->type_formule)) {
        ApiResponse::error("Données incomplètes. Nom, email et type de formule sont requis pour une mise à jour complète.", 400);
        exit;
    }
    
    
    $entreprise->nom = $data->nom;
    $entreprise->siret = $data->siret ?? $entreprise->siret;
    $entreprise->email = $data->email;
    $entreprise->telephone = $data->telephone ?? $entreprise->telephone;
    $entreprise->adresse = $data->adresse ?? $entreprise->adresse;
    $entreprise->type_formule = $data->type_formule;
}


if (isset($data->statut)) {
    $entreprise->statut = $data->statut;
}


if (isset($data->id_admin)) {
    $entreprise->id_admin = $data->id_admin;
}

if ($entreprise->update()) {
    ApiResponse::success(null, "Entreprise mise à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour l'entreprise", 500);
}
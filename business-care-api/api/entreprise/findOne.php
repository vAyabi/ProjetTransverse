<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID d'entreprise invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$entreprise->id_entreprise = intval($_GET["id"]);

if ($entreprise->findOne()) {
    $entreprise_arr = array(
        "id_entreprise" => $entreprise->id_entreprise,
        "nom" => $entreprise->nom,
        "siret" => $entreprise->siret,
        "email" => $entreprise->email,
        "telephone" => $entreprise->telephone,
        "adresse" => $entreprise->adresse,
        "code_entreprise" => $entreprise->code_entreprise,
        "type_formule" => $entreprise->type_formule,
        "statut" => $entreprise->statut,
        "created_at" => $entreprise->created_at
    );
    
    ApiResponse::success($entreprise_arr, "Entreprise récupérée avec succès");
} else {
    ApiResponse::notFound("Entreprise non trouvée");
}
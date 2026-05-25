<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Evenement.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID d'événement invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$evenement = new Evenement($db);
$evenement->id_evenement = intval($_GET["id"]);

if ($evenement->findOne()) {
    $evenement_arr = array(
        "id_evenement" => $evenement->id_evenement,
        "titre" => $evenement->titre,
        "description" => $evenement->description,
        "type_evenement" => $evenement->type_evenement,
        "date_debut" => $evenement->date_debut,
        "date_fin" => $evenement->date_fin,
        "capacite_max" => $evenement->capacite_max,
        "statut" => $evenement->statut,
        "created_at" => $evenement->created_at,
        "id_prestataire" => $evenement->id_prestataire,
        "id_entreprise" => $evenement->id_entreprise,
        "prestataire_nom" => $evenement->prestataire_nom,
        "entreprise_nom" => $evenement->entreprise_nom
    );
    
    ApiResponse::success($evenement_arr, "Événement récupéré avec succès");
} else {
    ApiResponse::notFound("Événement non trouvé");
}
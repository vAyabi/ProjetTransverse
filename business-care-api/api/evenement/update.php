<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Evenement.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('update')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->id_evenement) ||
    empty($data->titre) ||
    empty($data->type_evenement) ||
    empty($data->date_debut) ||
    empty($data->date_fin) ||
    empty($data->id_prestataire) ||
    empty($data->id_entreprise)
) {
    ApiResponse::error("Données incomplètes. ID événement, titre, type, dates, ID prestataire et ID entreprise sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$evenement = new Evenement($db);
$evenement->id_evenement = $data->id_evenement;
$evenement->titre = $data->titre;
$evenement->description = $data->description ?? null;
$evenement->type_evenement = $data->type_evenement;
$evenement->date_debut = $data->date_debut;
$evenement->date_fin = $data->date_fin;
$evenement->capacite_max = $data->capacite_max ?? null;
$evenement->statut = $data->statut ?? 'programmé';
$evenement->id_prestataire = $data->id_prestataire;
$evenement->id_entreprise = $data->id_entreprise;

if ($evenement->update()) {
    ApiResponse::success(null, "Événement mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour l'événement", 500);
}
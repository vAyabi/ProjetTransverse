<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Contrat.php';
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
    empty($data->date_debut) ||
    empty($data->date_fin) ||
    empty($data->montant_total) ||
    empty($data->type_paiement) ||
    empty($data->id_entreprise)
) {
    ApiResponse::error("Données incomplètes. Date de début, date de fin, montant, type de paiement et ID entreprise sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$contrat = new Contrat($db);
$contrat->date_debut = $data->date_debut;
$contrat->date_fin = $data->date_fin;
$contrat->montant_total = $data->montant_total;
$contrat->type_paiement = $data->type_paiement;
$contrat->statut = $data->statut ?? 'actif';
$contrat->id_entreprise = $data->id_entreprise;

if ($contrat->create()) {
    ApiResponse::success(
        array("id_contrat" => $db->lastInsertId()),
        "Contrat créé avec succès",
        201
    );
} else {
    ApiResponse::error("Impossible de créer le contrat", 500);
}
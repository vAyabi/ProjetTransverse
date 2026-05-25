<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Contrat.php';
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
    empty($data->id_contrat) ||
    empty($data->date_debut) ||
    empty($data->date_fin) ||
    empty($data->montant_total) ||
    empty($data->type_paiement)
) {
    ApiResponse::error("Données incomplètes. ID contrat, dates, montant et type de paiement sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$contrat = new Contrat($db);
$contrat->id_contrat = $data->id_contrat;

if (!$contrat->findOne()) {
    ApiResponse::notFound("Contrat non trouvé");
    exit;
}

$contrat->date_debut = $data->date_debut;
$contrat->date_fin = $data->date_fin;
$contrat->montant_total = $data->montant_total;
$contrat->type_paiement = $data->type_paiement;
$contrat->statut = $data->statut ?? $contrat->statut;

if ($contrat->update()) {
    ApiResponse::success(null, "Contrat mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour le contrat", 500);
}
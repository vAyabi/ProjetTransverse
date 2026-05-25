<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Contrat.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID de contrat invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$contrat = new Contrat($db);
$contrat->id_contrat = intval($_GET["id"]);

if ($contrat->findOne()) {
    $contrat_arr = array(
        "id_contrat" => $contrat->id_contrat,
        "date_debut" => $contrat->date_debut,
        "date_fin" => $contrat->date_fin,
        "montant_total" => $contrat->montant_total,
        "type_paiement" => $contrat->type_paiement,
        "statut" => $contrat->statut,
        "created_at" => $contrat->created_at,
        "id_entreprise" => $contrat->id_entreprise,
        "entreprise_nom" => $contrat->entreprise_nom
    );
    
    ApiResponse::success($contrat_arr, "Contrat récupéré avec succès");
} else {
    ApiResponse::notFound("Contrat non trouvé");
}
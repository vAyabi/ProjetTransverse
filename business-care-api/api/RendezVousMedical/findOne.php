<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/RendezVousMedical.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID de rendez-vous invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$rdv = new RendezVousMedical($db);
$rdv->id_rdv = intval($_GET["id"]);

if ($rdv->findOne()) {
    $rdv_arr = array(
        "id_rdv" => $rdv->id_rdv,
        "id_salarie" => $rdv->id_salarie,
        "id_prestataire" => $rdv->id_prestataire,
        "date_heure" => $rdv->date_heure,
        "type" => $rdv->type,
        "notes" => $rdv->notes,
        "statut" => $rdv->statut,
        "hors_quota" => $rdv->hors_quota,
        "created_at" => $rdv->created_at,
        "salarie_nom" => $rdv->salarie_nom,
        "prestataire_nom" => $rdv->prestataire_nom
    );
    
    ApiResponse::success($rdv_arr, "Rendez-vous récupéré avec succès");
} else {
    ApiResponse::notFound("Rendez-vous non trouvé");
}
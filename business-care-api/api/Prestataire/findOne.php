<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Prestataire.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID de prestataire invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$prestataire = new Prestataire($db);
$prestataire->id_prestataire = intval($_GET["id"]);

if ($prestataire->findOne()) {
    $prestataire_arr = array(
        "id_prestataire" => $prestataire->id_prestataire,
        "nom" => $prestataire->nom,
        "specialite" => $prestataire->specialite,
        "email" => $prestataire->email,
        "telephone" => $prestataire->telephone,
        "rib" => $prestataire->rib,
        "type_prestation" => $prestataire->type_prestation,
        "tarif_horaire" => $prestataire->tarif_horaire,
        "statut_validation" => $prestataire->statut_validation,
        "created_at" => $prestataire->created_at
    );
    
    ApiResponse::success($prestataire_arr, "Prestataire récupéré avec succès");
} else {
    ApiResponse::notFound("Prestataire non trouvé");
}
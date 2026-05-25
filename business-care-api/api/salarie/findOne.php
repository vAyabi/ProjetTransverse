<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID de salarié invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$salarie = new Salarie($db);
$salarie->id_salarie = intval($_GET["id"]);

if ($salarie->findOne()) {
    $salarie_arr = array(
        "id_salarie" => $salarie->id_salarie,
        "nom" => $salarie->nom,
        "email" => $salarie->email,
        "statut" => $salarie->statut,
        "first_login" => $salarie->first_login,
        "created_at" => $salarie->created_at,
        "id_entreprise" => $salarie->id_entreprise
    );
    
    ApiResponse::success($salarie_arr, "Salarié récupéré avec succès");
} else {
    ApiResponse::notFound("Salarié non trouvé");
}
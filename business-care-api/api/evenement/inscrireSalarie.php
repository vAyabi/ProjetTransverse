<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Evenement.php';
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
    empty($data->id_evenement) ||
    empty($data->id_salarie)
) {
    ApiResponse::error("Données incomplètes. ID de l'événement et ID du salarié sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$evenement = new Evenement($db);
$evenement->id_evenement = $data->id_evenement;

if (!$evenement->findOne()) {
    ApiResponse::notFound("Événement non trouvé");
    exit;
}


if ($evenement->isSalarieInscrit($data->id_salarie)) {
    ApiResponse::error("Ce salarié est déjà inscrit à cet événement", 400);
    exit;
}

if ($evenement->inscrireSalarie($data->id_salarie)) {
    ApiResponse::success(null, "Salarié inscrit avec succès à l'événement");
} else {
    ApiResponse::error("Impossible d'inscrire le salarié à l'événement", 500);
}
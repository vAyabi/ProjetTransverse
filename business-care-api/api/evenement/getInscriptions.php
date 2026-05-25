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


if (!$evenement->findOne()) {
    ApiResponse::notFound("Événement non trouvé");
    exit;
}

$stmt = $evenement->getInscriptions();
$num = $stmt->rowCount();

if ($num > 0) {
    $inscriptions_arr = array();
    $inscriptions_arr["inscriptions"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $inscription_item = array(
            "id_salarie" => $id_salarie,
            "nom" => $nom,
            "email" => $email,
            "date_inscription" => $date_inscription,
            "statut" => $statut
        );
        
        array_push($inscriptions_arr["inscriptions"], $inscription_item);
    }
    
    ApiResponse::success($inscriptions_arr, "Inscriptions récupérées avec succès");
} else {
    ApiResponse::success(array("inscriptions" => array()), "Aucune inscription trouvée pour cet événement");
}
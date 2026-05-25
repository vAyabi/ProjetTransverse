<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}


if (empty($_GET['id'])) {
    ApiResponse::error("ID de l'entreprise original non fourni", 400);
    exit;
}

$id_entreprise_original = $_GET['id'];

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$result = $entreprise->findArchivedEmployees($id_entreprise_original);

if ($result) {
    $salaries = array();
    $salaries["salaries"] = array();
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $salarie_item = array(
            "id_archive" => $id_archive,
            "id_salarie_original" => $id_salarie_original,
            "id_entreprise_original" => $id_entreprise_original,
            "nom" => $nom,
            "email" => $email,
            "statut" => $statut,
            "date_archivage" => $date_archivage,
            "raison_archivage" => $raison_archivage ?? "Non spécifié"
        );
        
        array_push($salaries["salaries"], $salarie_item);
    }
    
    ApiResponse::success($salaries, "Salariés archivés récupérés avec succès");
} else {
    ApiResponse::error("Aucun salarié archivé trouvé pour cette entreprise", 404);
}
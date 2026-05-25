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

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$result = $entreprise->findAllArchived();

if ($result) {
    $entreprises = array();
    $entreprises["entreprises_archivees"] = array();
    
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $entreprise_item = array(
            "id_archive" => $id_archive,
            "id_entreprise_original" => $id_entreprise_original,
            "nom" => $nom,
            "siret" => $siret,
            "email" => $email,
            "telephone" => $telephone,
            "adresse" => $adresse,
            "code_entreprise" => $code_entreprise,
            "type_formule" => $type_formule,
            "date_archivage" => $date_archivage,
            "raison_archivage" => $raison_archivage
        );
        
        array_push($entreprises["entreprises_archivees"], $entreprise_item);
    }
    
    ApiResponse::success($entreprises, "Entreprises archivées récupérées avec succès");
} else {
    ApiResponse::error("Aucune entreprise archivée trouvée", 404);
}
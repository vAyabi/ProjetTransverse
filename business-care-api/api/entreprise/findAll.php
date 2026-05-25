<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$entreprise = new Entreprise($db);
$stmt = $entreprise->findAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $entreprises_arr = array();
    $entreprises_arr["entreprises"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $entreprise_item = array(
            "id_entreprise" => $id_entreprise,
            "nom" => $nom,
            "siret" => $siret,
            "email" => $email,
            "telephone" => $telephone,
            "adresse" => $adresse,
            "code_entreprise" => $code_entreprise,
            "type_formule" => $type_formule,
            "statut" => $statut,
            "created_at" => $created_at
        );
        
        array_push($entreprises_arr["entreprises"], $entreprise_item);
    }
    
    ApiResponse::success($entreprises_arr, "Entreprises récupérées avec succès");
} else {
    ApiResponse::success(array("entreprises" => array()), "Aucune entreprise trouvée");
}
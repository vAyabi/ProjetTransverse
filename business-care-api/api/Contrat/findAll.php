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

$database = new Database();
$db = $database->getConnection();

$contrat = new Contrat($db);
$stmt = $contrat->findAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $contrats_arr = array();
    $contrats_arr["contrats"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $contrat_item = array(
            "id_contrat" => $id_contrat,
            "date_debut" => $date_debut,
            "date_fin" => $date_fin,
            "montant_total" => $montant_total,
            "type_paiement" => $type_paiement,
            "statut" => $statut,
            "created_at" => $created_at,
            "id_entreprise" => $id_entreprise,
            "entreprise_nom" => $entreprise_nom
        );
        
        array_push($contrats_arr["contrats"], $contrat_item);
    }
    
    ApiResponse::success($contrats_arr, "Contrats récupérés avec succès");
} else {
    ApiResponse::success(array("contrats" => array()), "Aucun contrat trouvé");
}
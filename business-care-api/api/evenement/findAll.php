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

$database = new Database();
$db = $database->getConnection();

$evenement = new Evenement($db);
$stmt = $evenement->findAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $evenements_arr = array();
    $evenements_arr["evenements"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $evenement_item = array(
            "id_evenement" => $id_evenement,
            "titre" => $titre,
            "description" => $description,
            "type_evenement" => $type_evenement,
            "date_debut" => $date_debut,
            "date_fin" => $date_fin,
            "capacite_max" => $capacite_max,
            "statut" => $statut,
            "created_at" => $created_at,
            "id_prestataire" => $id_prestataire,
            "id_entreprise" => $id_entreprise,
            "prestataire_nom" => $prestataire_nom,
            "entreprise_nom" => $entreprise_nom
        );
        
        array_push($evenements_arr["evenements"], $evenement_item);
    }
    
    ApiResponse::success($evenements_arr, "Événements récupérés avec succès");
} else {
    ApiResponse::success(array("evenements" => array()), "Aucun événement trouvé");
}
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

$database = new Database();
$db = $database->getConnection();

$prestataire = new Prestataire($db);
$stmt = $prestataire->findAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $prestataires_arr = array();
    $prestataires_arr["prestataires"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $prestataire_item = array(
            "id_prestataire" => $id_prestataire,
            "nom" => $nom,
            "specialite" => $specialite,
            "email" => $email,
            "telephone" => $telephone,
            "type_prestation" => $type_prestation,
            "tarif_horaire" => $tarif_horaire,
            "statut_validation" => $statut_validation,
            "created_at" => $created_at
        );
        
        array_push($prestataires_arr["prestataires"], $prestataire_item);
    }
    
    ApiResponse::success($prestataires_arr, "Prestataires récupérés avec succès");
} else {
    ApiResponse::success(array("prestataires" => array()), "Aucun prestataire trouvé");
}
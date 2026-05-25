<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


include_once '../../config/Database.php';
include_once '../../models/Devis.php';
include_once '../../utils/ApiResponse.php';


$database = new Database();
$db = $database->getConnection();

$devis = new Devis($db);


$data = json_decode(file_get_contents("php://input"));


if(
    !empty($data->id_entreprise) &&
    !empty($data->montant_total) &&
    !empty($data->validite_jours)
) {
    
    $devis->id_entreprise = $data->id_entreprise;
    $devis->montant_total = $data->montant_total;
    $devis->validite_jours = $data->validite_jours;
    $devis->statut = $data->statut ?? 'en_attente';

   
    if($devis->create()) {
        ApiResponse::success(null, "Devis créé avec succès");
    } else {
        ApiResponse::error("Impossible de créer le devis");
    }
} else {
    ApiResponse::error("Données incomplètes");
}
?>
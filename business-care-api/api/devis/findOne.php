<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


include_once '../../config/Database.php';
include_once '../../models/Devis.php';
include_once '../../utils/ApiResponse.php';


$database = new Database();
$db = $database->getConnection();

$devis = new Devis($db);


$devis->id_devis = isset($_GET['id']) ? $_GET['id'] : die();


if($devis->findOne()) {
    
    $devis_arr = [
        "id_devis" => $devis->id_devis,
        "id_entreprise" => $devis->id_entreprise,
        "entreprise_nom" => $devis->entreprise_nom,
        "montant_total" => $devis->montant_total,
        "validite_jours" => $devis->validite_jours,
        "statut" => $devis->statut,
        "created_at" => $devis->created_at
    ];

    
    ApiResponse::success(["devis" => $devis_arr]);
} else {
    ApiResponse::notFound("Devis non trouvé");
}
?>
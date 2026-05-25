<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");


include_once '../../config/Database.php';
include_once '../../models/Devis.php';
include_once '../../utils/ApiResponse.php';


$database = new Database();
$db = $database->getConnection();

$devis = new Devis($db);


$stmt = $devis->findAll();
$num = $stmt->rowCount();


if($num > 0) {
    
    $devis_arr = [];
    $devis_arr["devis"] = [];

    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);

        $devis_item = [
            "id_devis" => $id_devis,
            "id_entreprise" => $id_entreprise,
            "entreprise_nom" => $entreprise_nom,
            "montant_total" => $montant_total,
            "validite_jours" => $validite_jours,
            "statut" => $statut,
            "created_at" => $created_at
        ];

        array_push($devis_arr["devis"], $devis_item);
    }

    
    ApiResponse::success($devis_arr);
} else {
    ApiResponse::success(["devis" => []]);
}
?>
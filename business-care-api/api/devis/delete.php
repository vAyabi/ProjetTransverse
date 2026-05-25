<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: DELETE");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


include_once '../../config/Database.php';
include_once '../../models/Devis.php';
include_once '../../utils/ApiResponse.php';


$database = new Database();
$db = $database->getConnection();

$devis = new Devis($db);


$data = json_decode(file_get_contents("php://input"));


if(empty($data->id_devis)) {
    ApiResponse::error("ID du devis manquant");
    exit();
}


$devis->id_devis = $data->id_devis;


if(!$devis->findOne()) {
    ApiResponse::notFound("Devis non trouvé");
    exit();
}


if($devis->delete()) {
    ApiResponse::success(null, "Devis supprimé avec succès");
} else {
    ApiResponse::error("Impossible de supprimer le devis");
}
?>
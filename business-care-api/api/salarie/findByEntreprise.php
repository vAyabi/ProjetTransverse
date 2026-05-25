<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id_entreprise"]) || !verifyPositiveInteger($_GET["id_entreprise"])) {
    ApiResponse::error("ID d'entreprise invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$salarie = new Salarie($db);
$salarie->id_entreprise = intval($_GET["id_entreprise"]);

$stmt = $salarie->findByEntreprise();
$num = $stmt->rowCount();

if ($num > 0) {
    $salaries_arr = array();
    $salaries_arr["salaries"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $salarie_item = array(
            "id_salarie" => $id_salarie,
            "nom" => $nom,
            "email" => $email,
            "statut" => $statut,
            "first_login" => $first_login,
            "created_at" => $created_at,
            "id_entreprise" => $id_entreprise
        );
        
        array_push($salaries_arr["salaries"], $salarie_item);
    }
    
    ApiResponse::success($salaries_arr, "Salariés récupérés avec succès");
} else {
    ApiResponse::success(array("salaries" => array()), "Aucun salarié trouvé pour cette entreprise");
}
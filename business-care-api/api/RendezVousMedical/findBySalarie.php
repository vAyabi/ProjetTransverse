<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/RendezVousMedical.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

if (!methodIsAllowed('read')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

if (empty($_GET["id_salarie"]) || !verifyPositiveInteger($_GET["id_salarie"])) {
    ApiResponse::error("ID de salarié invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$rdv = new RendezVousMedical($db);
$rdv->id_salarie = intval($_GET["id_salarie"]);

$stmt = $rdv->findBySalarie();
$num = $stmt->rowCount();

if ($num > 0) {
    $rdvs_arr = array();
    $rdvs_arr["rendez_vous"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $rdv_item = array(
            "id_rdv" => $id_rdv,
            "id_salarie" => $id_salarie,
            "id_prestataire" => $id_prestataire,
            "date_heure" => $date_heure,
            "type" => $type,
            "notes" => $notes,
            "statut" => $statut,
            "hors_quota" => $hors_quota,
            "created_at" => $created_at,
            "prestataire_nom" => $prestataire_nom
        );
        
        array_push($rdvs_arr["rendez_vous"], $rdv_item);
    }
    
    ApiResponse::success($rdvs_arr, "Rendez-vous récupérés avec succès");
} 
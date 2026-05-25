<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Admin.php';
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

$admin = new Admin($db);
$stmt = $admin->findAll();
$num = $stmt->rowCount();

if ($num > 0) {
    $admins_arr = array();
    $admins_arr["admins"] = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $admin_item = array(
            "id_admin" => $id_admin,
            "nom" => $nom,
            "email" => $email,
            "role" => $role,
            "permissions" => $permissions,
            "created_at" => $created_at
        );
        
        array_push($admins_arr["admins"], $admin_item);
    }
    
    ApiResponse::success($admins_arr, "Administrateurs récupérés avec succès");
} else {
    ApiResponse::success(array("admins" => array()), "Aucun administrateur trouvé");
}
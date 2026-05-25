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

if (empty($_GET["id"]) || !verifyPositiveInteger($_GET["id"])) {
    ApiResponse::error("ID d'administrateur invalide", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->id_admin = intval($_GET["id"]);

if ($admin->findOne()) {
    $admin_arr = array(
        "id_admin" => $admin->id_admin,
        "nom" => $admin->nom,
        "email" => $admin->email,
        "role" => $admin->role,
        "permissions" => $admin->permissions,
        "created_at" => $admin->created_at
    );
    
    ApiResponse::success($admin_arr, "Administrateur récupéré avec succès");
} else {
    ApiResponse::notFound("Administrateur non trouvé");
}
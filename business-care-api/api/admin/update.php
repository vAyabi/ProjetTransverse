<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Admin.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: PUT, POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('update')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->id_admin) ||
    empty($data->nom) ||
    empty($data->email) ||
    empty($data->role)
) {
    ApiResponse::error("Données incomplètes. ID, nom, email et rôle sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->id_admin = $data->id_admin;

if (!$admin->findOne()) {
    ApiResponse::notFound("Administrateur non trouvé");
    exit;
}

$admin->nom = $data->nom;
$admin->email = $data->email;
$admin->role = $data->role;
$admin->permissions = $data->permissions ?? $admin->permissions;

if ($admin->update()) {
    ApiResponse::success(null, "Administrateur mis à jour avec succès");
} else {
    ApiResponse::error("Impossible de mettre à jour l'administrateur", 500);
}
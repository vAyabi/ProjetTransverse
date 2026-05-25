<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Admin.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if (!methodIsAllowed('create')) {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}

$data = json_decode(file_get_contents("php://input"));

if (
    empty($data->nom) ||
    empty($data->email) ||
    empty($data->password) ||
    empty($data->role)
) {
    ApiResponse::error("Données incomplètes. Nom, email, mot de passe et rôle sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->email = $data->email;

if ($admin->emailExists()) {
    ApiResponse::error("Un administrateur avec cet email existe déjà.", 400);
    exit;
}

$admin->nom = $data->nom;
$admin->password = $data->password;
$admin->role = $data->role;
$admin->permissions = $data->permissions ?? null;

if ($admin->create()) {
    ApiResponse::success(
        array("id_admin" => $db->lastInsertId()),
        "Administrateur créé avec succès",
        201
    );
} else {
    ApiResponse::error("Impossible de créer l'administrateur", 500);
}
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Salarie.php';
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
    empty($data->email) ||
    empty($data->password)
) {
    ApiResponse::error("Email et mot de passe sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$salarie = new Salarie($db);
$salarie->email = $data->email;

if ($salarie->emailExists()) {
    
    if (hash('sha256', $data->password) == $salarie->password) {
        
        if ($salarie->statut != 1) {
            ApiResponse::error("Compte désactivé. Veuillez contacter votre administrateur.", 403);
            exit;
        }
        
        
        $token = bin2hex(random_bytes(32));
        
        ApiResponse::success(
            array(
                "token" => $token,
                "id_salarie" => $salarie->id_salarie,
                "nom" => $salarie->nom,
                "email" => $salarie->email,
                "first_login" => $salarie->first_login,
                "id_entreprise" => $salarie->id_entreprise
            ),
            "Connexion réussie"
        );
    } else {
        ApiResponse::error("Mot de passe incorrect", 401);
    }
} else {
    ApiResponse::error("Email non trouvé", 404);
}
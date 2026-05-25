<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
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

$entreprise = new Entreprise($db);
$entreprise->email = $data->email;

if ($entreprise->findByEmail()) {
    
    if (hash('sha256', $data->password) == $entreprise->password) {
        
        if ($entreprise->statut != 1) {
            ApiResponse::error("Compte désactivé. Veuillez contacter l'administrateur.", 403);
            exit;
        }
        
        
        $token = bin2hex(random_bytes(32));
        
        ApiResponse::success(
            array(
                "token" => $token,
                "id_entreprise" => $entreprise->id_entreprise,
                "nom" => $entreprise->nom,
                "email" => $entreprise->email,
                "type_formule" => $entreprise->type_formule,
                "code_entreprise" => $entreprise->code_entreprise
            ),
            "Connexion réussie"
        );
    } else {
        ApiResponse::error("Mot de passe incorrect", 401);
    }
} else {
    ApiResponse::error("Email non trouvé", 404);
}
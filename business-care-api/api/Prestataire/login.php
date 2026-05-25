<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Prestataire.php';
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

$prestataire = new Prestataire($db);
$prestataire->email = $data->email;

if ($prestataire->emailExists()) {
   
    if (hash('sha256', $data->password) == $prestataire->password) {
        
        if ($prestataire->statut_validation !== 'validé') {
            ApiResponse::error("Votre compte n'est pas encore validé ou a été refusé.", 403);
            exit;
        }
        
        
        $token = bin2hex(random_bytes(32));
        
        ApiResponse::success(
            array(
                "token" => $token,
                "id_prestataire" => $prestataire->id_prestataire,
                "nom" => $prestataire->nom,
                "email" => $prestataire->email,
                "specialite" => $prestataire->specialite,
                "type_prestation" => $prestataire->type_prestation
            ),
            "Connexion réussie"
        );
    } else {
        ApiResponse::error("Mot de passe incorrect", 401);
    }
} else {
    ApiResponse::error("Email non trouvé", 404);
}
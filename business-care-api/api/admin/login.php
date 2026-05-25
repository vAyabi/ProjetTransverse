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
    empty($data->email) ||
    empty($data->password)
) {
    ApiResponse::error("Email et mot de passe sont requis.", 400);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->email = $data->email;

if ($admin->emailExists()) {
    // Appliquer le même hashage que dans l'ancienne version
    $salt = 'IF7EFECFGC%SDH';
    $password_salt = $data->password . $salt;
    $password_hash = hash('sha256', $password_salt);
    
    // Vérifier le mot de passe
    if ($password_hash == $admin->password) {
        // Créer token simple pour l'exemple (dans une application réelle, utilisez JWT)
        $token = bin2hex(random_bytes(32));
        
        ApiResponse::success(
            array(
                "token" => $token,
                "id_admin" => $admin->id_admin,
                "email" => $admin->email,
                "role" => "admin"
            ),
            "Connexion réussie"
        );
    } else {
        ApiResponse::error("Mot de passe incorrect", 401);
    }
} else {
    ApiResponse::error("Email non trouvé", 404);
}
?>
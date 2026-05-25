<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/ApiResponse.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/utils/Server.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::error("Méthode non autorisée", 405);
    exit;
}


$data = json_decode(file_get_contents("php://input"));

if (empty($data->action) || empty($data->id_signalement)) {
    ApiResponse::error("Données incomplètes. Action et ID du signalement sont requis.", 400);
    exit;
}

$database = new Database();
$conn = $database->getConnection();


$stmt_check = $conn->prepare("SELECT id_signalement FROM signalements WHERE id_signalement = ?");
$stmt_check->execute([$data->id_signalement]);
if (!$stmt_check->fetch()) {
    ApiResponse::notFound("Signalement introuvable");
    exit;
}


switch ($data->action) {
    case 'update_status':
        if (empty($data->nouveau_statut)) {
            ApiResponse::error("Le nouveau statut est requis", 400);
            exit;
        }
        
        
        if (!in_array($data->nouveau_statut, ['nouveau', 'en_traitement', 'traité'])) {
            ApiResponse::error("Statut invalide", 400);
            exit;
        }
        
        $sql_update = "UPDATE signalements SET statut = ? WHERE id_signalement = ?";
        $stmt_update = $conn->prepare($sql_update);
        
        if ($stmt_update->execute([$data->nouveau_statut, $data->id_signalement])) {
            ApiResponse::success(null, "Le statut du signalement a été mis à jour avec succès.");
        } else {
            ApiResponse::error("Une erreur est survenue lors de la mise à jour du statut.", 500);
        }
        break;
        
    case 'add_response':
        if (empty($data->contenu)) {
            ApiResponse::error("Le contenu de la réponse est requis", 400);
            exit;
        }
        
        $sql_insert = "INSERT INTO signalements_reponses (id_signalement, contenu, date_reponse) VALUES (?, ?, CURRENT_TIMESTAMP)";
        $stmt_insert = $conn->prepare($sql_insert);
        
        if ($stmt_insert->execute([$data->id_signalement, $data->contenu])) {
            
            $sql_check = "SELECT statut FROM signalements WHERE id_signalement = ? AND statut = 'nouveau'";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->execute([$data->id_signalement]);
            
            if ($stmt_check->fetch()) {
                $sql_update = "UPDATE signalements SET statut = 'en_traitement' WHERE id_signalement = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->execute([$data->id_signalement]);
            }
            
            ApiResponse::success(null, "La réponse a été ajoutée avec succès.");
        } else {
            ApiResponse::error("Une erreur est survenue lors de l'ajout de la réponse.", 500);
        }
        break;
        
    default:
        ApiResponse::error("Action non reconnue", 400);
        break;
}
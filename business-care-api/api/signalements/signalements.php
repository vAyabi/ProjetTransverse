<?php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");


include_once '../config/Database.php';
include_once '../models/Signalement.php';


$database = new Database();
$db = $database->getConnection();


$signalement = new Signalement($db);


if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['id'])) {
        
        $signalement->getById($_GET['id']);

        if ($signalement->id_signalement != null) {
            
            $stmt_responses = $signalement->getResponses();
            $responses = [];
            
            while ($row = $stmt_responses->fetch(PDO::FETCH_ASSOC)) {
                $responses[] = [
                    'id_reponse' => $row['id_reponse'],
                    'contenu' => $row['contenu'],
                    'date_reponse' => $row['date_reponse']
                ];
            }
            
           
            $signalement_arr = [
                'id_signalement' => $signalement->id_signalement,
                'contenu' => $signalement->contenu,
                'statut' => $signalement->statut,
                'date_signalement' => $signalement->date_signalement,
                'id_salarie' => $signalement->id_salarie,
                'type' => $signalement->type,
                'urgence' => $signalement->urgence,
                'anonyme' => $signalement->anonyme,
                'nom_salarie' => $signalement->anonyme ? 'Anonyme' : $signalement->nom_salarie,
                'email_salarie' => $signalement->anonyme ? 'Anonyme' : $signalement->email_salarie,
                'nom_entreprise' => $signalement->nom_entreprise,
                'id_entreprise' => $signalement->id_entreprise,
                'reponses' => $responses
            ];

            
            http_response_code(200);
            echo json_encode($signalement_arr);
        } else {
            
            http_response_code(404);
            echo json_encode(["message" => "Signalement introuvable"]);
        }
    } else {
        
        $stmt = $signalement->getAllSignalements();
        $num = $stmt->rowCount();

        if ($num > 0) {
            $signalements_arr = [];

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);

                $signalement_item = [
                    'id_signalement' => $id_signalement,
                    'contenu' => $contenu,
                    'statut' => $statut,
                    'date_signalement' => $date_signalement,
                    'type' => $type,
                    'urgence' => $urgence,
                    'nom_salarie' => $anonyme ? 'Anonyme' : $nom_salarie,
                    'nom_entreprise' => $nom_entreprise
                ];

                array_push($signalements_arr, $signalement_item);
            }

            http_response_code(200);
            echo json_encode($signalements_arr);
        } else {
            http_response_code(404);
            echo json_encode(["message" => "Aucun signalement trouvé"]);
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
   
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id_signalement) && !empty($data->statut)) {
        $signalement->id_signalement = $data->id_signalement;
        $signalement->statut = $data->statut;

        if ($signalement->updateStatus()) {
            http_response_code(200);
            echo json_encode(["message" => "Statut du signalement mis à jour avec succès"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Impossible de mettre à jour le statut du signalement"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Données incomplètes"]);
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->id_signalement) && !empty($data->contenu)) {
        $signalement->id_signalement = $data->id_signalement;

        if ($signalement->addResponse($data->contenu)) {
            http_response_code(201);
            echo json_encode(["message" => "Réponse ajoutée avec succès"]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Impossible d'ajouter la réponse"]);
        }
    } else {
        http_response_code(400);
        echo json_encode(["message" => "Données incomplètes"]);
    }
}
?>
<?php
session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if(!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de l'entreprise manquant";
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];


function callAPI($endpoint, $method = 'GET', $data = null) {
    $apiUrl = "http://localhost/business-care-api/api/" . $endpoint;
    
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $headers = ['Content-Type: application/json'];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $_SESSION['error'] = "Erreur API: " . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}


$data = [
    'id_entreprise' => intval($id),
    'statut' => 1
];


$result = callAPI("entreprise/update.php", 'PUT', $data);



if($result && isset($result['status']) && $result['status']) {
    $_SESSION['success'] = "Entreprise validée avec succès";
} else {
    $message = isset($result['message']) ? $result['message'] : "Erreur lors de la validation de l'entreprise";
    $_SESSION['error'] = "Erreur: " . $message;
}

header('Location: index.php');
exit();
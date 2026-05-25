<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID non fourni";
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

if(isset($_GET['id'])) {
    try {
        
        $id = $_GET['id'];
        $result = callAPI("Prestataire/findOne.php?id=$id");
        
        if (!$result || empty($result['data'])) {
            $_SESSION['error'] = "Prestataire non trouvé";
            header('Location: index.php');
            exit();
        }
        
        $prestataire = $result['data'];
        
       
        if ($prestataire['statut_validation'] === 'en_attente') {
            
            $data = [
                'id_prestataire' => $id,
                'statut_validation' => 'validé'
            ];
            
            
            $data['nom'] = $prestataire['nom'];
            $data['email'] = $prestataire['email'];
            $data['specialite'] = $prestataire['specialite'];
            $data['type_prestation'] = $prestataire['type_prestation'];
            $data['tarif_horaire'] = $prestataire['tarif_horaire'];
            $data['telephone'] = $prestataire['telephone'];
            $data['rib'] = $prestataire['rib'];
            
            $updateResult = callAPI("Prestataire/update.php", 'PUT', $data);
            
            if ($updateResult && !isset($updateResult['error'])) {
                $_SESSION['success'] = "Prestataire validé avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la validation";
            }
        } else {
            $_SESSION['error'] = "Le prestataire n'est pas en attente de validation";
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
}

header('Location: index.php');
exit();
?>
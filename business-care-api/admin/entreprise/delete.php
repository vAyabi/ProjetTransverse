<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID de l'entreprise manquant";
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];


function callAPI($endpoint, $method = 'GET', $data = null) {
    $apiUrl = "http://localhost/business-care-api/api/" . $endpoint;
    
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
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

try {
    
    $entrepriseResult = callAPI("entreprise/findOne.php?id=$id");
    if (!$entrepriseResult || !isset($entrepriseResult['status']) || !$entrepriseResult['status']) {
        $_SESSION['error'] = "Entreprise non trouvée";
        header('Location: index.php');
        exit();
    }
    
   
    $salarieResult = callAPI("salarie/findByEntreprise.php?id_entreprise=$id");
    if ($salarieResult && isset($salarieResult['data']['salaries']) && !empty($salarieResult['data']['salaries'])) {
    
        $_SESSION['error'] = "Impossible de supprimer cette entreprise car elle a des salariés associés";
        header('Location: index.php');
        exit();
        
        
    }
    
    
    $contratResult = callAPI("contrat/findByEntreprise.php?id_entreprise=$id");
    if ($contratResult && isset($contratResult['data']['contrats']) && !empty($contratResult['data']['contrats'])) {
        
        $_SESSION['error'] = "Impossible de supprimer cette entreprise car elle a des contrats associés";
        header('Location: index.php');
        exit();
        
        
    }
    
    
    $deleteResult = callAPI("entreprise/delete.php?id=$id", 'DELETE');
    
    if ($deleteResult && isset($deleteResult['status']) && $deleteResult['status']) {
        $_SESSION['success'] = "Entreprise supprimée avec succès";
    } else {
        $message = isset($deleteResult['message']) ? $deleteResult['message'] : "Erreur lors de la suppression";
        throw new Exception($message);
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

header('Location: index.php');
exit();
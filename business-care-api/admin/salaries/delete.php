<?php
session_start();

// Vérifier l'authentification admin
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

// Vérifier l'ID du salarié
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID du salarié manquant";
    header('Location: index.php');
    exit();
}

$id = intval($_GET['id']);

// Fonction pour appeler l'API
function callAPI($endpoint, $method = 'GET', $data = null) {
    $apiUrl = "http://localhost/business-care-api/api/" . $endpoint;
    
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            error_log("Données envoyées: " . json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $headers = ['Content-Type: application/json'];
    if (isset($_SESSION['admin_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['admin_token'];
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("Erreur cURL: " . curl_error($ch));
        $_SESSION['error'] = "Erreur API: " . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    
    error_log("Réponse API (HTTP $httpCode): " . $response);
    
    return json_decode($response, true);
}

try {
    // Vérifier que le salarié existe
    $salarieResult = callAPI("salarie/findOne.php?id=$id");
    
    if (!$salarieResult || !isset($salarieResult['status']) || !$salarieResult['status']) {
        $_SESSION['error'] = "Salarié non trouvé";
        header('Location: index.php');
        exit();
    }
    
    // Créer les données pour l'archivage
    $data = [
        'id_salarie' => $id,
        'raison_archivage' => 'Suppression par l\'administrateur'
    ];
    
    // Appeler l'endpoint d'archivage
    $result = callAPI("salarie/archive.php", 'POST', $data);
    
    error_log("Résultat archivage: " . json_encode($result));
    
    if ($result && isset($result['status']) && $result['status']) {
        $_SESSION['success'] = "Salarié archivé avec succès";
    } else {
        $message = isset($result['message']) ? $result['message'] : "Erreur lors de l'archivage";
        if (isset($result['error'])) {
            $message .= " - " . $result['error'];
        }
        $_SESSION['error'] = $message;
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    error_log("Erreur suppression salarié ID $id: " . $e->getMessage());
}

header('Location: index.php');
exit();
?>
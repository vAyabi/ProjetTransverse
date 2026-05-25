<?php
session_start();


if (isset($_SESSION['admin_token'])) {
    try {
        
        $apiUrl = 'http://localhost/business-care-api/logout.php';
        
        
        $ch = curl_init($apiUrl);
        
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $_SESSION['admin_token']
        ));
        
        
        curl_exec($ch);
        
        
        curl_close($ch);
    } catch (Exception $e) {
        
    }
}


session_destroy();


header('Location: index.php');
exit();
?>
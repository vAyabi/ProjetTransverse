<?php
session_start();

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        
        $data = array(
            'email' => $_POST['email'],
            'password' => $_POST['password'], 
            'role' => 'admin'
        );
        
        
        $apiUrl = 'http://localhost/business-care-api/api/admin/login.php';
        
        
        $ch = curl_init($apiUrl);
        
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json'
        ));
        
        
        $response = curl_exec($ch);
        
        
        curl_close($ch);
        
        
        $result = json_decode($response, true);
        
        if($result && $result['status'] === 'success') {
           
            $_SESSION['admin'] = true;
            $_SESSION['admin_id'] = $result['data']['id_admin'];
            $_SESSION['admin_email'] = $result['data']['email'];
            $_SESSION['admin_token'] = $result['data']['token']; 
            
            header("Location: dashboard.php");
            exit();
        } else {
           
            $_SESSION['error'] = isset($result['message']) ? $result['message'] : "Identifiants incorrects";
            header("Location: index.php");
            exit();
        }
        
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur de connexion : " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}
?>
<?php
session_start();
require_once '../../config/Database.php';

if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $query = "UPDATE prestataires 
                 SET nom = :nom,
                     email = :email,
                     telephone = :telephone,
                     rib = :rib";
                     
        if(!empty($_POST['password'])) {
            $query .= ", password = :password";
        }
        
        $query .= " WHERE id_prestataire = :id";
        
        $params = [
            ':nom' => $_POST['nom'],
            ':email' => $_POST['email'],
            ':telephone' => $_POST['telephone'],
            ':rib' => $_POST['rib'],
            ':id' => $_SESSION['user_id']
        ];
        
        if(!empty($_POST['password'])) {
            $salt = 'IF7EFECFGC%SDH';
            $password_salt = $_POST['password'] . $salt;
            $params[':password'] = hash('sha256', $password_salt);
        }
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute($params);
        
        if($result) {
            $_SESSION['success'] = "Profil mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour";
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour";
    }
}

header('Location: profil.php');
exit();
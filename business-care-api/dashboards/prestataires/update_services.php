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
                 SET type_prestation = :type_prestation,
                     specialite = :specialite,
                     tarif_horaire = :tarif_horaire
                 WHERE id_prestataire = :id";
                 
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':type_prestation' => $_POST['type_prestation'],
            ':specialite' => $_POST['specialite'],
            ':tarif_horaire' => $_POST['tarif_horaire'],
            ':id' => $_SESSION['user_id']
        ]);
        
        if($result) {
            $_SESSION['success'] = "Services mis à jour avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la mise à jour";
        }
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la mise à jour";
    }
}

header('Location: services.php');
exit();
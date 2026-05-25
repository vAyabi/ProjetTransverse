<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

if(isset($_GET['jour'])) {
    require_once '../../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $jour = $_GET['jour'];
    
    // Supprimer les disponibilités pour ce jour
    $stmt = $conn->prepare("DELETE FROM disponibilites_prestataires WHERE id_prestataire = ? AND jour_semaine = ?");
    $result = $stmt->execute([$_SESSION['user_id'], $jour]);
    
    if($result) {
        $_SESSION['success'] = "Disponibilités supprimées avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue.";
    }
}

header('Location: disponibilites.php');
exit();
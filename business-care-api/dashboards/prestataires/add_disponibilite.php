<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $jour = $_POST['jour'];
    $heure_debut = $_POST['heure_debut'];
    $heure_fin = $_POST['heure_fin'];
    
    // Vérifier que l'heure de fin est après l'heure de début
    if(strtotime($heure_fin) <= strtotime($heure_debut)) {
        $_SESSION['error'] = "L'heure de fin doit être après l'heure de début.";
        header('Location: disponibilites.php');
        exit();
    }
    
    // Insérer la disponibilité
    $stmt = $conn->prepare("INSERT INTO disponibilites_prestataires (id_prestataire, jour_semaine, heure_debut, heure_fin, statut) VALUES (?, ?, ?, ?, 'disponible')");
    $result = $stmt->execute([$_SESSION['user_id'], $jour, $heure_debut, $heure_fin]);
    
    if($result) {
        $_SESSION['success'] = "Disponibilité ajoutée avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue.";
    }
}

header('Location: disponibilites.php');
exit();
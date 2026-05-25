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
    
    $date_exception = $_POST['date_exception'];
    $statut = $_POST['statut'];
    
    // Vérifier si une exception existe déjà pour cette date
    $stmt = $conn->prepare("SELECT COUNT(*) FROM disponibilites_prestataires WHERE id_prestataire = ? AND date_exception = ?");
    $stmt->execute([$_SESSION['user_id'], $date_exception]);
    $existe = $stmt->fetchColumn();
    
    if($existe > 0) {
        $_SESSION['error'] = "Une exception existe déjà pour cette date.";
        header('Location: disponibilites.php');
        exit();
    }
    
    // Insérer l'exception
    if($statut == 'disponible') {
        $heure_debut = $_POST['heure_debut_exception'];
        $heure_fin = $_POST['heure_fin_exception'];
        
        // Vérifier que l'heure de fin est après l'heure de début
        if(strtotime($heure_fin) <= strtotime($heure_debut)) {
            $_SESSION['error'] = "L'heure de fin doit être après l'heure de début.";
            header('Location: disponibilites.php');
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO disponibilites_prestataires (id_prestataire, date_exception, heure_debut, heure_fin, statut) VALUES (?, ?, ?, ?, 'disponible')");
        $result = $stmt->execute([$_SESSION['user_id'], $date_exception, $heure_debut, $heure_fin]);
    } else {
        $stmt = $conn->prepare("INSERT INTO disponibilites_prestataires (id_prestataire, date_exception, statut) VALUES (?, ?, 'indisponible')");
        $result = $stmt->execute([$_SESSION['user_id'], $date_exception]);
    }
    
    if($result) {
        $_SESSION['success'] = "Exception ajoutée avec succès.";
    } else {
        $_SESSION['error'] = "Une erreur est survenue.";
    }
}

header('Location: disponibilites.php');
exit();
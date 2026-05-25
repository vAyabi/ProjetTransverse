<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

if(isset($_GET['id']) && is_numeric($_GET['id'])) {
    require_once '../../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Vérifier que l'exception appartient bien au prestataire
    $stmt = $conn->prepare("SELECT * FROM disponibilites_prestataires WHERE id_disponibilite = ? AND id_prestataire = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $exception = $stmt->fetch();
    
    if($exception) {
        // Supprimer l'exception
        $delete = $conn->prepare("DELETE FROM disponibilites_prestataires WHERE id_disponibilite = ?");
        $result = $delete->execute([$_GET['id']]);
        
        if($result) {
            $_SESSION['success'] = "Exception supprimée avec succès.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue.";
        }
    } else {
        $_SESSION['error'] = "Exception introuvable.";
    }
}

header('Location: disponibilites.php');
exit();
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
    
    // Vérifier que le rdv appartient bien au prestataire
    $stmt = $conn->prepare("SELECT * FROM rendez_vous_medicaux WHERE id_rdv = ? AND id_prestataire = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $rdv = $stmt->fetch();
    
    if($rdv) {
        // Mettre à jour le statut
        $update = $conn->prepare("UPDATE rendez_vous_medicaux SET statut = 'terminé' WHERE id_rdv = ?");
        $result = $update->execute([$_GET['id']]);
        
        if($result) {
            $_SESSION['success'] = "Le rendez-vous a été marqué comme terminé.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue.";
        }
    } else {
        $_SESSION['error'] = "Rendez-vous introuvable.";
    }
}

header("Location: rdv_medicaux.php");
exit();
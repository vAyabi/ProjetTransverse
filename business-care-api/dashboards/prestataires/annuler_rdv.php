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
        $update = $conn->prepare("UPDATE rendez_vous_medicaux SET statut = 'annulé' WHERE id_rdv = ?");
        $result = $update->execute([$_GET['id']]);
        
        if($result) {
            // Si c'est un RDV dans le quota, on réattribue le quota
            $stmt = $conn->prepare("SELECT hors_quota FROM rendez_vous_medicaux WHERE id_rdv = ?");
            $stmt->execute([$_GET['id']]);
            $hors_quota = $stmt->fetchColumn();
            
            if(!$hors_quota) {
                // Retrouver le mois et l'année du rdv
                $stmt = $conn->prepare("SELECT MONTH(date_heure) as mois, YEAR(date_heure) as annee, id_salarie FROM rendez_vous_medicaux WHERE id_rdv = ?");
                $stmt->execute([$_GET['id']]);
                $rdv_info = $stmt->fetch();
                
                // Incrémenter le quota disponible
                $update_quota = $conn->prepare("UPDATE quota_rdv_medicaux SET quota_disponible = quota_disponible + 1 WHERE id_salarie = ? AND mois = ? AND annee = ?");
                $update_quota->execute([$rdv_info['id_salarie'], $rdv_info['mois'], $rdv_info['annee']]);
            }
            
            $_SESSION['success'] = "Le rendez-vous a été annulé.";
        } else {
            $_SESSION['error'] = "Une erreur est survenue.";
        }
    } else {
        $_SESSION['error'] = "Rendez-vous introuvable.";
    }
}

header("Location: rdv_medicaux.php");
exit();
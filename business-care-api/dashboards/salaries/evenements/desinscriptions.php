<?php
// evenements/desinscription.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

if(!isset($_GET['id'])) {
    $_SESSION['error'] = "Événement non spécifié";
    header('Location: ../evenements.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Vérifier que l'inscription existe
    $stmt = $conn->prepare("
        SELECT ie.*, e.titre 
        FROM inscriptions_evenements ie 
        JOIN evenements e ON ie.id_evenement = e.id_evenement
        WHERE ie.id_evenement = ? 
        AND ie.id_salarie = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $inscription = $stmt->fetch();

    if(!$inscription) {
        throw new Exception("Vous n'êtes pas inscrit à cet événement");
    }

    // Supprimer l'inscription
    $stmt = $conn->prepare("
        DELETE FROM inscriptions_evenements 
        WHERE id_evenement = ? 
        AND id_salarie = ?
    ");
    
    if($stmt->execute([$_GET['id'], $_SESSION['user_id']])) {
        $_SESSION['success'] = "Vous êtes désinscrit de l'événement";
    } else {
        throw new Exception("Erreur lors de la désinscription");
    }

} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../evenements.php');
exit();
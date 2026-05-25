<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

// Vérifier le paramètre
if(!isset($_GET['id_association'])) {
    header('Location: index.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Enregistrer la participation
    $stmt = $conn->prepare("
        INSERT INTO participations_associations (id_salarie, id_association, type_participation) 
        VALUES (?, ?, 'don_financier')
    ");
    
    $stmt->execute([
        $_SESSION['user_id'],
        $_GET['id_association']
    ]);
    
    $_SESSION['success'] = "Votre don a été effectué avec succès. Merci pour votre générosité!";
} catch(Exception $e) {
    $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
}

// Rediriger vers la page d'accueil
header('Location: index.php');
exit();
<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Validation des données
        if(empty($_POST['contenu']) || empty($_POST['type']) || empty($_POST['urgence'])) {
            throw new Exception("Tous les champs obligatoires doivent être remplis.");
        }

        $stmt = $conn->prepare("
            INSERT INTO signalements (
                contenu, 
                type,
                urgence,
                anonyme,
                id_salarie
            ) VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['contenu'],
            $_POST['type'],
            $_POST['urgence'],
            isset($_POST['anonyme']) ? 1 : 0,
            $_SESSION['user_id']
        ]);
        
        $_SESSION['success'] = "Votre signalement a bien été enregistré.";
    } catch(Exception $e) {
        $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
    }
}

header('Location: ../signalement.php');
exit();
?>
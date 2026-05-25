<?php
// profil/update_profile.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // Vérifier si l'email est déjà utilisé
        $stmt = $conn->prepare("SELECT id_salarie FROM salaries WHERE email = ? AND id_salarie != ?");
        $stmt->execute([$_POST['email'], $_SESSION['user_id']]);
        if($stmt->fetch()) {
            throw new Exception("Cette adresse email est déjà utilisée");
        }

        $stmt = $conn->prepare("
            UPDATE salaries 
            SET nom = ?, email = ?
            WHERE id_salarie = ?
        ");
        
        if($stmt->execute([
            $_POST['nom'],
            $_POST['email'],
            $_SESSION['user_id']
        ])) {
            $_SESSION['success'] = "Profil mis à jour avec succès";
        } else {
            throw new Exception("Erreur lors de la mise à jour");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: index.php');
exit();
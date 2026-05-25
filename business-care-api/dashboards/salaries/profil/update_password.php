<?php
// profil/update_password.php
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

        // Vérifier que les nouveaux mots de passe correspondent
        if($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("Les nouveaux mots de passe ne correspondent pas");
        }

        // Vérifier l'ancien mot de passe
        $stmt = $conn->prepare("SELECT password FROM salaries WHERE id_salarie = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if(!$user || !password_verify($_POST['current_password'], $user['password'])) {
            throw new Exception("Mot de passe actuel incorrect");
        }

        // Mettre à jour le mot de passe
        $new_password_hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("UPDATE salaries SET password = ? WHERE id_salarie = ?");
        if($stmt->execute([$new_password_hash, $_SESSION['user_id']])) {
            $_SESSION['success'] = "Mot de passe modifié avec succès";
        } else {
            throw new Exception("Erreur lors de la mise à jour du mot de passe");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: index.php');
exit();
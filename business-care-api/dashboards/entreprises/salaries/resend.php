<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    require_once '../../../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();

    if(!isset($_GET['id'])) {
        $_SESSION['error'] = "ID du salarié manquant";
        header('Location: ../salaries.php');
        exit();
    }

    // Générer nouveau mot de passe
    $temp_password = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $salt = 'IF7EFECFGC%SDH';
    $password_salt = $temp_password . $salt;
    $hashed_password = hash('sha256', $password_salt);

    $stmt = $conn->prepare("UPDATE salaries SET password = ? WHERE id_salarie = ? AND id_entreprise = ?");
    if($stmt->execute([$hashed_password, $_GET['id'], $_SESSION['user_id']])) {
        $_SESSION['success'] = "Le mot de passe a été réinitialisé. La fonctionnalité d'envoi d'email sera bientôt disponible.";
    } else {
        $_SESSION['error'] = "Erreur lors de la réinitialisation du mot de passe";
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue";
}

header('Location: ../salaries.php');
exit();
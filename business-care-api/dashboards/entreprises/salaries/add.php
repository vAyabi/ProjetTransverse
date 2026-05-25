<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';
require_once '../../../config/mail.php';
require_once '../../../classes/Mailer.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // pour debug: afficher le contenu de $_POST
    var_dump($_POST);

    
    $stmt = $conn->prepare("SELECT COUNT(*) FROM salaries WHERE email = ?");
    $stmt->execute([$_POST['email']]);
    if($stmt->fetchColumn() > 0) {
        throw new Exception("Un salarié avec cet email existe déjà");
    }

    // Générer code d'activation
    $activation_code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
    $salt = 'IF7EFECFGC%SDH';
    $password_salt = $activation_code . $salt;
    $hashed_password = hash('sha256', $password_salt);

    
    $stmt = $conn->prepare("
        INSERT INTO salaries (nom, email, password, statut, first_login, id_entreprise) 
        VALUES (?, ?, ?, 0, 1, ?)
    ");
    
    if($stmt->execute([$_POST['nom'], $_POST['email'], $hashed_password, $_SESSION['user_id']])) {
        
        echo "Insertion réussie<br>";

        // Tentative d'envoi d'email
        $mailer = new Mailer();
        if($mailer->sendInvitation($_POST['email'], $_POST['nom'], $activation_code)) {
            $_SESSION['success'] = "Le salarié a été ajouté et l'invitation a été envoyée";
        } else {
            $_SESSION['warning'] = "Le salarié a été ajouté mais l'email n'a pas pu être envoyé";
        }
    } else {
        throw new Exception("Erreur lors de l'ajout du salarié");
    }

} catch(Exception $e) {
    
    echo "Erreur : " . $e->getMessage() . "<br>";
    echo "Fichier : " . $e->getFile() . "<br>";
    echo "Ligne : " . $e->getLine() . "<br>";
    
    $_SESSION['error'] = $e->getMessage();
}

// Attendre 3 secondes pour voir les messages de debug
sleep(3);

header('Location: ../salaries.php');
exit();
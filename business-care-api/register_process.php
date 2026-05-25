<?php
session_start();
require_once 'config/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (!isset($_POST['type'])) {
            throw new Exception("Type de compte non sélectionné");
        }

        if (strlen($_POST['password']) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
        }

        if ($_POST['password'] !== $_POST['password_confirm']) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        if (!$email) {
            throw new Exception("Email invalide");
        }

        $db = new Database();
        $conn = $db->getConnection();

        $tables = ['entreprises', 'salaries', 'prestataires', 'admin'];
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT email FROM {$table} WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                throw new Exception("Cet email est déjà utilisé");
            }
        }

        $salt = 'IF7EFECFGC%SDH';
        $password_salt = $_POST['password'] . $salt;
        $hashed_password = hash('sha256', $password_salt);

        switch($_POST['type']) {
            case 'entreprises':
                if (!preg_match("/^[0-9]{14}$/", $_POST['siret'])) {
                    throw new Exception("SIRET invalide (14 chiffres requis)");
                }

                $code_entreprise = strtoupper(substr($_POST['nom'], 0, 3) . uniqid());
                
                $sql = "INSERT INTO entreprises (
                    id_entreprise,
                    nom,
                    siret,
                    email,
                    password, 
                    telephone,
                    adresse,
                    code_entreprise,
                    type_formule,
                    statut,
                    created_at,
                    date_inscription,
                    id_admin
                ) VALUES (
                    NULL,      
                    :nom,
                    :siret,
                    :email,
                    :password, 
                    :telephone,
                    :adresse,
                    :code_entreprise,
                    :type_formule,
                    1,         
                    CURRENT_TIMESTAMP,
                    CURRENT_TIMESTAMP,
                    NULL       
                )";

                $params = [
                    ':nom' => $_POST['nom'],
                    ':siret' => $_POST['siret'],
                    ':email' => $email,
                    ':password' => $hashed_password,  
                    ':telephone' => $_POST['telephone'],
                    ':adresse' => $_POST['adresse'],
                    ':code_entreprise' => $code_entreprise,
                    ':type_formule' => $_POST['type_formule']
                ];
                break;

            case 'prestataires':
                if (!preg_match("/^[A-Z0-9]{27}$/i", $_POST['rib'])) {
                    throw new Exception("Format RIB invalide");
                }

                $sql = "INSERT INTO prestataires (
                    nom,
                    specialite,
                    email,
                    password,
                    telephone,
                    rib,
                    type_prestation,
                    tarif_horaire,
                    statut_validation
                ) VALUES (
                    :nom,
                    :specialite,
                    :email,
                    :password,
                    :telephone,
                    :rib,
                    :type_prestation,
                    :tarif_horaire,
                    'en_attente'
                )";
                
                $params = [
                    ':nom' => $_POST['nom'],
                    ':specialite' => $_POST['specialite'],
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':telephone' => $_POST['telephone'],
                    ':rib' => $_POST['rib'],
                    ':type_prestation' => $_POST['type_prestation'],
                    ':tarif_horaire' => 0 
                ];
                break;

            default:
                throw new Exception("Type de compte invalide");
        }

        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute($params)) {
            $_SESSION['success'] = "Inscription réussie  Vous pouvez maintenant vous connecter ";
            if ($_POST['type'] === 'prestataires') {
                $_SESSION['success'] .= " Votre compte est en attente de validation ";
            }
            header("Location: login.php");
            exit();
        }

        throw new Exception("Erreur lors de l'inscription");

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: register.php");
        exit();
    }
}
?>
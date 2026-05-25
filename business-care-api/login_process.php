<?php
session_start();
require_once './config/Database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $user_type = $_POST['user_type'];
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        
        // hachage du mot de passe avec le même salt
        $salt = 'IF7EFECFGC%SDH';
        $password_salt = $_POST['password'] . $salt;
        $hashed_password = hash('sha256', $password_salt);

        // validations
        if (!$email || !$_POST['password'] || !$user_type) {
            throw new Exception("Tous les champs sont obligatoires");
        }

        // requête avec password haché
        $sql = "SELECT * FROM {$user_type} WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === $hashed_password) {
        
            // vérification de première connexion pour les salariés
            if($user_type === 'salaries' && isset($user['first_login']) && $user['first_login'] == 1) {
                $_SESSION['temp_user_id'] = $user['id_salarie'];
                $_SESSION['temp_user_email'] = $user['email'];
                $_SESSION['temp_user_type'] = $user_type;
                header('Location: first-login.php');
                exit();
            }

            // vérifications supplémentaires selon le type
            switch($user_type) {
                case 'prestataires':
                    // Vérifier si le champ existe avant de le comparer
                    if (isset($user['statut_validation']) && $user['statut_validation'] !== 'validé') {
                        // pour debug
                        throw new Exception("Votre compte est en attente de validation. Statut actuel: " . $user['statut_validation']);
                    }
                    break;
                case 'entreprises':
                    if (isset($user['statut']) && !$user['statut']) {
                        throw new Exception("Compte inactif");
                    }
                    break;
                case 'salaries':
                    if (isset($user['statut']) && !$user['statut']) {
                        throw new Exception("Compte inactif");
                    }
                    break;
            }

            // config de la session
            $_SESSION['logged_in'] = true;
            switch($user_type) {
                case 'prestataires':
                    $_SESSION['user_id'] = $user['id_prestataire'];
                    break;
                case 'entreprises':
                    $_SESSION['user_id'] = $user['id_entreprise'];
                    break;
                case 'salaries':
                    $_SESSION['user_id'] = $user['id_salarie'];
                    break;
                case 'admin':
                    $_SESSION['user_id'] = $user['id_admin'];
                    break;
            }
            $_SESSION['user_type'] = $user_type;
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_nom'] = $user['nom'];

            header("Location: /business-care-api/dashboards/{$user_type}/index.php");
            exit();
        } else {
            throw new Exception("Email ou mot de passe incorrect");
        }

    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: login.php");
        exit();
    }
}
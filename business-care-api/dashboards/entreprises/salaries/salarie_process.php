<?php
session_start();
require_once '../../config/Database.php';


if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();
    $notifications = new Notifications($conn);

    
    $action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

    switch($action) {
        
        case 'add':
            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM salaries WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            if($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Un salarié avec cet email existe déjà";
                break;
            }

            // Générer un mot de passe temporaire
            $temp_password = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            
            
            $salt = 'IF7EFECFGC%SDH';
            $password_salt = $temp_password . $salt;
            $hashed_password = hash('sha256', $password_salt);

            
            $query = "INSERT INTO salaries (nom, email, password, statut, id_entreprise) 
                     VALUES (:nom, :email, :password, 0, :id_entreprise)";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':nom' => $_POST['nom'],
                ':email' => $_POST['email'],
                ':password' => $hashed_password,
                ':id_entreprise' => $_SESSION['user_id']
            ]);

           

                // Envoi de l'email d'invitation
                $to = $_POST['email'];
                $subject = "Invitation Business Care";
                $message = "Bonjour " . htmlspecialchars($_POST['nom']) . ",\n\n";
                $message .= "Vous avez été invité à rejoindre Business Care.\n\n";
                $message .= "Vos identifiants de connexion :\n";
                $message .= "Email : " . $_POST['email'] . "\n";
                $message .= "Mot de passe : " . $temp_password . "\n\n";
                $message .= "Connectez-vous sur : http://localhost/business-care-api/\n";
                $message .= "Pensez à changer votre mot de passe à la première connexion.\n";
                
                $headers = 'From: noreply@businesscare.fr' . "\r\n";
                mail($to, $subject, $message, $headers);

                $_SESSION['success'] = "Le salarié a été ajouté et l'invitation a été envoyée";
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du salarié";
            }
            break;

        
        case 'edit':
            
            $stmt = $conn->prepare("SELECT id_salarie FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
            $stmt->execute([$_POST['id_salarie'], $_SESSION['user_id']]);
            if(!$stmt->fetch()) {
                $_SESSION['error'] = "Salarié non trouvé";
                break;
            }

            $query = "UPDATE salaries 
                     SET nom = :nom, email = :email 
                     WHERE id_salarie = :id AND id_entreprise = :id_entreprise";
            $stmt = $conn->prepare($query);
            $result = $stmt->execute([
                ':nom' => $_POST['nom'],
                ':email' => $_POST['email'],
                ':id' => $_POST['id_salarie'],
                ':id_entreprise' => $_SESSION['user_id']
            ]);

            if($result) {
                $_SESSION['success'] = "Salarié modifié avec succès";
            } else {
                $_SESSION['error'] = "Erreur lors de la modification";
            }
            break;

        
        case 'delete':
            if(!isset($_GET['id'])) {
                $_SESSION['error'] = "ID du salarié manquant";
                break;
            }

            
            $stmt = $conn->prepare("SELECT COUNT(*) FROM inscriptions_evenements WHERE id_salarie = ?");
            $stmt->execute([$_GET['id']]);
            
            if($stmt->fetchColumn() > 0) {
                $_SESSION['error'] = "Impossible de supprimer ce salarié car il est inscrit à des événements";
            } else {
                $stmt = $conn->prepare("DELETE FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
                if($stmt->execute([$_GET['id'], $_SESSION['user_id']])) {
                    $_SESSION['success'] = "Salarié supprimé avec succès";
                } else {
                    $_SESSION['error'] = "Erreur lors de la suppression";
                }
            }
            break;

        
        case 'resend':
            if(!isset($_GET['id'])) {
                $_SESSION['error'] = "ID du salarié manquant";
                break;
            }

            // Générer nouveau mot de passe temporaire
            $temp_password = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $salt = 'IF7EFECFGC%SDH';
            $password_salt = $temp_password . $salt;
            $hashed_password = hash('sha256', $password_salt);

            // Récupérer les infos du salarié
            $stmt = $conn->prepare("SELECT * FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
            $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
            $salarie = $stmt->fetch();

            if($salarie) {
                // Mettre à jour le mot de passe
                $stmt = $conn->prepare("UPDATE salaries SET password = ? WHERE id_salarie = ?");
                if($stmt->execute([$hashed_password, $_GET['id']])) {
                    // Renvoyer l'email
                    $to = $salarie['email'];
                    $subject = "Nouvelle invitation Business Care";
                    $message = "Bonjour " . htmlspecialchars($salarie['nom']) . ",\n\n";
                    $message .= "Voici vos nouveaux identifiants :\n\n";
                    $message .= "Email : " . $salarie['email'] . "\n";
                    $message .= "Mot de passe : " . $temp_password . "\n\n";
                    $message .= "Connectez-vous sur : http://localhost/business-care-api/\n";
                    
                    $headers = 'From: noreply@businesscare.fr' . "\r\n";
                    mail($to, $subject, $message, $headers);

                    $_SESSION['success'] = "Nouvelle invitation envoyée";
                } else {
                    $_SESSION['error'] = "Erreur lors du renvoi de l'invitation";
                }
            } else {
                $_SESSION['error'] = "Salarié non trouvé";
            }
            break;

        default:
            $_SESSION['error'] = "Action non valide";
            break;
    }

 catch(PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
}


header('Location: salaries.php');
exit();
<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

try {
    // Gestion du retour de Stripe (success.php pourrait rediriger ici)
    if(isset($_GET['success']) && $_GET['success'] == 'true') {
        if(!isset($_GET['id_association'])) {
            throw new Exception("Paramètres manquants");
        }
        
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("
            INSERT INTO participations_associations (id_salarie, id_association, type_participation) 
            VALUES (?, ?, 'don_financier')
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_GET['id_association']
        ]);

        $_SESSION['success'] = "Votre don a bien été effectué et enregistré";
        header('Location: index.php?success=payment');
        exit;
    }

    // Traitement des formulaires POST (don matériel et bénévolat)
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        if(!isset($_POST['id_association']) || !isset($_POST['type_participation'])) {
            throw new Exception("Données manquantes");
        }
        
        $db = new Database();
        $conn = $db->getConnection();

        // Validation des champs spécifiques
        if($_POST['type_participation'] === 'don_materiel' && empty($_POST['description'])) {
            throw new Exception("Veuillez décrire votre don matériel");
        }

        if($_POST['type_participation'] === 'benevolat' && (empty($_POST['disponibilites']) || empty($_POST['competences']))) {
            throw new Exception("Veuillez remplir tous les champs pour le bénévolat");
        }

        // Enregistrer la participation de base
        $stmt = $conn->prepare("
            INSERT INTO participations_associations (id_salarie, id_association, type_participation) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['user_id'],
            $_POST['id_association'],
            $_POST['type_participation']
        ]);
        
        // Récupérer l'ID de la participation créée
        $participation_id = $conn->lastInsertId();
        
        // Stocker les détails supplémentaires
        $description = null;
        $disponibilites = null;
        $competences = null;

        if($_POST['type_participation'] === 'don_materiel') {
            $description = $_POST['description'];
        } else if($_POST['type_participation'] === 'benevolat') {
            $disponibilites = $_POST['disponibilites'];
            $competences = $_POST['competences'];
        }

        // Insérer les détails dans la table
        $stmt = $conn->prepare("
            INSERT INTO participation_details (id_participation, description, disponibilites, competences) 
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            $participation_id,
            $description,
            $disponibilites,
            $competences
        ]);

        $_SESSION['success'] = "Votre participation a bien été enregistrée";
    }
} catch(Exception $e) {
    $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
}

header('Location: index.php');
exit;
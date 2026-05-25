<?php
// evenements/inscription.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

if(!isset($_GET['id'])) {
    $_SESSION['error'] = "Événement non spécifié";
    header('Location: ../evenements.php');
    exit();
}

try {
    $db = new Database();
    $conn = $db->getConnection();

    // Vérifier que l'événement existe et est accessible
    $stmt = $conn->prepare("
        SELECT e.*, 
               (SELECT COUNT(*) FROM inscriptions_evenements WHERE id_evenement = e.id_evenement) as nb_inscrits
        FROM evenements e
        WHERE e.id_evenement = ? 
        AND e.id_entreprise = (
            SELECT id_entreprise FROM salaries WHERE id_salarie = ?
        )
        AND e.statut = 'programmé'
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $event = $stmt->fetch();

    if(!$event) {
        throw new Exception("Événement non trouvé");
    }

    // Vérifier que le salarié n'est pas déjà inscrit
    $stmt = $conn->prepare("SELECT id_salarie FROM inscriptions_evenements WHERE id_evenement = ? AND id_salarie = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    if($stmt->fetch()) {
        throw new Exception("Vous êtes déjà inscrit à cet événement");
    }

    // Vérifier la capacité
    if($event['capacite_max'] && $event['nb_inscrits'] >= $event['capacite_max']) {
        throw new Exception("L'événement est complet");
    }

    // Créer l'inscription
    $stmt = $conn->prepare("
        INSERT INTO inscriptions_evenements (id_salarie, id_evenement, statut)
        VALUES (?, ?, 'inscrit')
    ");
    
    if($stmt->execute([$_SESSION['user_id'], $_GET['id']])) {
        $_SESSION['success'] = "Inscription réussie !";
    } else {
        throw new Exception("Erreur lors de l'inscription");
    }

} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header('Location: ../evenements.php');
exit();
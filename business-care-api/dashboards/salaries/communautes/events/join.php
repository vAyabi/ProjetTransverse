<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer l'ID du salarié connecté
$id_salarie = $_SESSION['user_id'] ?? 0;

// Vérifier ID événement et statut
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['statut'])) {
    header('Location: ../index.php');
    exit;
}

$id_evenement = $_GET['id'];
$statut = $_GET['statut'];

// Valider statut
$statuts_valides = ['confirme', 'peut_etre', 'refuse', 'annuler'];
if (!in_array($statut, $statuts_valides)) {
    $_SESSION['error'] = "Statut invalide.";
    header('Location: view.php?id=' . $id_evenement);
    exit;
}

try {
    // Récupérer la communauté de l'événement
    $stmt = $conn->prepare("SELECT id_communaute FROM communautes_evenements WHERE id_evenement_communaute = ?");
    $stmt->execute([$id_evenement]);
    $evenement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$evenement) {
        $_SESSION['error'] = "Événement introuvable.";
        header('Location: ../index.php');
        exit;
    }
    
    // Vérifier si membre
    $stmt = $conn->prepare("
        SELECT id_membre FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$evenement['id_communaute'], $id_salarie]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Vous devez être membre pour participer.";
        header('Location: view.php?id=' . $id_evenement);
        exit;
    }
    
    // Vérifier si déjà inscrit
    $stmt = $conn->prepare("
        SELECT id_participant FROM communautes_participants 
        WHERE id_evenement_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_evenement, $id_salarie]);
    $participant = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($statut == 'annuler') {
        // Supprimer la participation
        if ($participant) {
            $stmt = $conn->prepare("
                DELETE FROM communautes_participants 
                WHERE id_evenement_communaute = ? AND id_salarie = ?
            ");
            $stmt->execute([$id_evenement, $id_salarie]);
            
            $_SESSION['success'] = "Votre participation a été annulée.";
        } else {
            $_SESSION['info'] = "Vous n'étiez pas inscrit à cet événement.";
        }
    } else {
        // Ajouter ou mettre à jour la participation
        if ($participant) {
            $stmt = $conn->prepare("
                UPDATE communautes_participants 
                SET statut = ?, date_reponse = NOW()
                WHERE id_evenement_communaute = ? AND id_salarie = ?
            ");
            $stmt->execute([$statut, $id_evenement, $id_salarie]);
        } else {
            $stmt = $conn->prepare("
                INSERT INTO communautes_participants (id_evenement_communaute, id_salarie, statut)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$id_evenement, $id_salarie, $statut]);
        }
        
        $_SESSION['success'] = "Votre participation a été enregistrée.";
    }
    
    header('Location: view.php?id=' . $id_evenement);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: view.php?id=' . $id_evenement);
}
exit;
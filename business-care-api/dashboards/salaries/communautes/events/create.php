<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer l'ID du salarié connecté
$id_salarie = $_SESSION['user_id'] ?? 0;

// Vérifier formulaire
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_communaute'])) {
    header('Location: ../index.php');
    exit;
}

$id_communaute = $_POST['id_communaute'];
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$lieu = trim($_POST['lieu'] ?? '');
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';

// Validation basique
if (empty($titre) || empty($description) || empty($date_debut) || empty($date_fin)) {
    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
    header('Location: ../view.php?id=' . $id_communaute);
    exit;
}

// Valider que date_fin > date_debut
if (strtotime($date_fin) <= strtotime($date_debut)) {
    $_SESSION['error'] = "La date de fin doit être postérieure à la date de début.";
    header('Location: ../view.php?id=' . $id_communaute);
    exit;
}

// Formater correctement les dates pour MySQL
$date_debut_mysql = date('Y-m-d H:i:s', strtotime($date_debut));
$date_fin_mysql = date('Y-m-d H:i:s', strtotime($date_fin));

try {
    // Vérifier si membre
    $stmt = $conn->prepare("
        SELECT id_membre FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Vous devez être membre pour créer un événement.";
        header('Location: ../view.php?id=' . $id_communaute);
        exit;
    }
    
    // Créer l'événement
    $stmt = $conn->prepare("
        INSERT INTO communautes_evenements (id_communaute, titre, description, lieu, date_debut, date_fin, id_createur)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$id_communaute, $titre, $description, $lieu, $date_debut_mysql, $date_fin_mysql, $id_salarie]);
    
    $id_evenement = $conn->lastInsertId();
    
    // Inscrire le créateur automatiquement
    $stmt = $conn->prepare("
        INSERT INTO communautes_participants (id_evenement_communaute, id_salarie, statut)
        VALUES (?, ?, 'confirme')
    ");
    $stmt->execute([$id_evenement, $id_salarie]);
    
    // Vérifier que l'événement a bien été créé
    $check_stmt = $conn->prepare("SELECT * FROM communautes_evenements WHERE id_evenement_communaute = ?");
    $check_stmt->execute([$id_evenement]);
    $created_event = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($created_event) {
        $_SESSION['success'] = "Événement créé avec succès.";
    } else {
        $_SESSION['warning'] = "L'événement a été enregistré mais avec une possible erreur. Vérifiez dans la liste.";
    }
    
    header('Location: ../view.php?id=' . $id_communaute);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    // Log l'erreur pour débogage
    error_log("Erreur création événement: " . $e->getMessage());
    header('Location: ../view.php?id=' . $id_communaute);
}
exit;
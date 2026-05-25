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

// Vérifier ID communauté
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Communauté invalide.";
    header('Location: index.php');
    exit;
}

$id_communaute = $_GET['id'];

try {
    // Vérifier si déjà membre
    $stmt = $conn->prepare("
        SELECT id_membre FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    if ($stmt->fetch()) {
        $_SESSION['info'] = "Vous êtes déjà membre de cette communauté.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }
    
    // Ajouter comme membre
    $stmt = $conn->prepare("
        INSERT INTO communautes_membres (id_communaute, id_salarie, role)
        VALUES (?, ?, 'membre')
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    $_SESSION['success'] = "Vous avez rejoint la communauté!";
    header('Location: view.php?id=' . $id_communaute);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: index.php');
}
exit;
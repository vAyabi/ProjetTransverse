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

// Vérifier ID publication et communauté
if (!isset($_GET['id']) || !isset($_GET['communaute']) || !is_numeric($_GET['id']) || !is_numeric($_GET['communaute'])) {
    header('Location: index.php');
    exit;
}

$id_publication = $_GET['id'];
$id_communaute = $_GET['communaute'];

try {
    // Vérifier si propriétaire ou admin
    $stmt = $conn->prepare("
        SELECT cp.id_auteur, cm.role
        FROM communautes_publications cp
        LEFT JOIN communautes_membres cm ON cp.id_communaute = cm.id_communaute AND cm.id_salarie = ?
        WHERE cp.id_publication = ? AND cp.id_communaute = ?
    ");
    $stmt->execute([$id_salarie, $id_publication, $id_communaute]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        $_SESSION['error'] = "Publication introuvable.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }
    
    // Vérifier si l'utilisateur est l'auteur ou un admin
    if ($result['id_auteur'] != $id_salarie && $result['role'] != 'admin') {
        $_SESSION['error'] = "Vous n'avez pas le droit de supprimer cette publication.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }
    
    // Supprimer la publication
    $stmt = $conn->prepare("DELETE FROM communautes_publications WHERE id_publication = ?");
    $stmt->execute([$id_publication]);
    
    $_SESSION['success'] = "Publication supprimée avec succès.";
    header('Location: view.php?id=' . $id_communaute);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: view.php?id=' . $id_communaute);
}
exit;
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

// Vérifier données formulaire
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_communaute']) || !isset($_POST['contenu'])) {
    header('Location: index.php');
    exit;
}

$id_communaute = $_POST['id_communaute'];
$contenu = trim($_POST['contenu']);

if (empty($contenu)) {
    $_SESSION['error'] = "Le message ne peut pas être vide.";
    header('Location: view.php?id=' . $id_communaute);
    exit;
}

try {
    // Vérifier si membre
    $stmt = $conn->prepare("
        SELECT id_membre FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    if (!$stmt->fetch()) {
        $_SESSION['error'] = "Vous devez être membre pour publier.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }
    
    // Modération automatique (filtrage basique)
    $mots_interdits = ['insulte', 'grossier', 'mot_inapproprie'];
    foreach ($mots_interdits as $mot) {
        $contenu = str_ireplace($mot, '***', $contenu);
    }
    
    // Ajouter la publication
    $stmt = $conn->prepare("
        INSERT INTO communautes_publications (id_communaute, id_auteur, contenu, modere)
        VALUES (?, ?, ?, 1)
    ");
    $stmt->execute([$id_communaute, $id_salarie, $contenu]);
    
    $_SESSION['success'] = "Message publié avec succès.";
    header('Location: view.php?id=' . $id_communaute);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: view.php?id=' . $id_communaute);
}
exit;
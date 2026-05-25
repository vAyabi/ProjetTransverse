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

// Vérifier si formulaire soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nom = trim($_POST['nom'] ?? '');
$description = trim($_POST['description'] ?? '');
$categorie = $_POST['categorie'] ?? 'autre';

// Validation basique
if (empty($nom) || empty($description)) {
    $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
    header('Location: index.php');
    exit;
}

// Traitement de l'image
$image_path = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/assets/images/communautes/';
    
    // Créer le répertoire si nécessaire
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = uniqid('comm_') . '.' . $file_ext;
    $target_path = $upload_dir . $file_name;
    
    // Vérifier que c'est une image
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_ext), $allowed_types)) {
        $_SESSION['error'] = "Format d'image non supporté. Utilisez JPG, PNG ou GIF.";
        header('Location: index.php');
        exit;
    }
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
        $image_path = 'assets/images/communautes/' . $file_name;
    } else {
        $_SESSION['error'] = "Erreur lors du téléchargement de l'image.";
        header('Location: index.php');
        exit;
    }
}

try {
    // Créer la communauté
    $stmt = $conn->prepare("
        INSERT INTO communautes (nom, description, categorie, id_createur, image)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nom, $description, $categorie, $id_salarie, $image_path]);
    
    $id_communaute = $conn->lastInsertId();
    
    // Ajouter le créateur comme admin
    $stmt = $conn->prepare("
        INSERT INTO communautes_membres (id_communaute, id_salarie, role)
        VALUES (?, ?, 'admin')
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    $_SESSION['success'] = "Communauté créée avec succès!";
    header('Location: view.php?id=' . $id_communaute);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la création: " . $e->getMessage();
    header('Location: index.php');
}
exit;
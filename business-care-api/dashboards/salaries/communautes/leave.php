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
    // Vérifier si membre et non admin
    $stmt = $conn->prepare("
        SELECT role FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    $membre = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$membre) {
        $_SESSION['error'] = "Vous n'êtes pas membre de cette communauté.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }
    
    // Les admins ne peuvent pas quitter (sauf si créateur et s'il y a d'autres admins)
    if ($membre['role'] == 'admin') {
        // Vérifier si c'est le créateur
        $stmt = $conn->prepare("SELECT id_createur FROM communautes WHERE id_communaute = ?");
        $stmt->execute([$id_communaute]);
        $communaute = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($communaute['id_createur'] == $id_salarie) {
            // Vérifier s'il y a d'autres admins
            $stmt = $conn->prepare("
                SELECT COUNT(*) as nb_admins FROM communautes_membres 
                WHERE id_communaute = ? AND role = 'admin' AND id_salarie != ?
            ");
            $stmt->execute([$id_communaute, $id_salarie]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['nb_admins'] == 0) {
                $_SESSION['error'] = "Vous ne pouvez pas quitter la communauté car vous êtes le seul administrateur.";
                header('Location: view.php?id=' . $id_communaute);
                exit;
            }
        }
    }
    
    // Supprimer le membre
    $stmt = $conn->prepare("
        DELETE FROM communautes_membres 
        WHERE id_communaute = ? AND id_salarie = ?
    ");
    $stmt->execute([$id_communaute, $id_salarie]);
    
    $_SESSION['success'] = "Vous avez quitté la communauté.";
    header('Location: index.php');
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: view.php?id=' . $id_communaute);
}
exit;
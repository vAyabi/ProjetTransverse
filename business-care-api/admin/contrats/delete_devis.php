<?php
session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID du devis invalide";
    header('Location: index.php');
    exit();
}

require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    
    $check_query = "SELECT id_devis FROM devis WHERE id_devis = :id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':id' => $_GET['id']]);
    
    if(!$check_stmt->fetch()) {
        $_SESSION['error'] = "Devis non trouvé";
        header('Location: index.php');
        exit();
    }

   
    $query = "DELETE FROM devis WHERE id_devis = :id";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([':id' => $_GET['id']]);

    if($result) {
        $_SESSION['success'] = "Devis supprimé avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la suppression";
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}

header('Location: index.php');
exit();
?>
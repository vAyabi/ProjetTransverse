<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    require_once '../../../config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();

    if(!isset($_GET['id'])) {
        $_SESSION['error'] = "ID du salarié manquant";
        header('Location: ../salaries.php');
        exit();
    }

    
    $stmt = $conn->prepare("SELECT id_salarie FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    if(!$stmt->fetch()) {
        $_SESSION['error'] = "Salarié non trouvé";
        header('Location: ../salaries.php');
        exit();
    }

   
    $stmt = $conn->prepare("SELECT COUNT(*) FROM inscriptions_evenements WHERE id_salarie = ?");
    $stmt->execute([$_GET['id']]);
    
    if($stmt->fetchColumn() > 0) {
        $_SESSION['error'] = "Impossible de supprimer ce salarié car il est inscrit à des événements";
    } else {
        $stmt = $conn->prepare("DELETE FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
        if($stmt->execute([$_GET['id'], $_SESSION['user_id']])) {
            $_SESSION['success'] = "Salarié supprimé avec succès";
        } else {
            $_SESSION['error'] = "Erreur lors de la suppression";
        }
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue";
}

header('Location: ../salaries.php');
exit();
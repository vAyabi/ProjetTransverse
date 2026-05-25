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

    if(!isset($_POST['id_salarie'])) {
        $_SESSION['error'] = "ID du salarié manquant";
        header('Location: ../salaries.php');
        exit();
    }

    
    $stmt = $conn->prepare("SELECT * FROM salaries WHERE id_salarie = ? AND id_entreprise = ?");
    $stmt->execute([$_POST['id_salarie'], $_SESSION['user_id']]);
    $salarie = $stmt->fetch();

    if(!$salarie) {
        $_SESSION['error'] = "Salarié non trouvé";
        header('Location: ../salaries.php');
        exit();
    }

    
    if($salarie['email'] !== $_POST['email']) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM salaries WHERE email = ? AND id_salarie != ?");
        $stmt->execute([$_POST['email'], $_POST['id_salarie']]);
        if($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Cet email est déjà utilisé";
            header('Location: ../salaries.php');
            exit();
        }
    }

    $stmt = $conn->prepare("UPDATE salaries SET nom = ?, email = ? WHERE id_salarie = ? AND id_entreprise = ?");
    if($stmt->execute([$_POST['nom'], $_POST['email'], $_POST['id_salarie'], $_SESSION['user_id']])) {
        $_SESSION['success'] = "Salarié modifié avec succès";
    } else {
        $_SESSION['error'] = "Erreur lors de la modification";
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue";
}

header('Location: ../salaries.php');
exit();
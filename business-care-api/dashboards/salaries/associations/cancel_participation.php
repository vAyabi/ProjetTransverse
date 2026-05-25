<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_participation'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier que la participation appartient bien à ce salarié et est de type bénévolat
        $stmt = $conn->prepare("
            SELECT * FROM participations_associations
            WHERE id_participation = ? AND id_salarie = ? AND type_participation = 'benevolat'
        ");
        
        $stmt->execute([
            $_POST['id_participation'],
            $_SESSION['user_id']
        ]);
        
        $participation = $stmt->fetch();
        
        if(!$participation) {
            throw new Exception("Participation non trouvée ou non autorisée");
        }
        
        // Supprimer les détails associés (la contrainte FK ON DELETE CASCADE s'occupera d'eux)
        $stmt = $conn->prepare("DELETE FROM participations_associations WHERE id_participation = ?");
        $stmt->execute([$_POST['id_participation']]);
        
        $_SESSION['success'] = "Votre participation a bien été annulée";
    } catch(Exception $e) {
        $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
    }
}

header('Location: index.php');
exit;
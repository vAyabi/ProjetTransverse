<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID du contrat invalide";
    header('Location: index.php');
    exit();
}

require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    
    $id_contrat = $_GET['id'];
    
    
    $check_query = "SELECT id_contrat FROM contrats WHERE id_contrat = :id";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute([':id' => $id_contrat]);
    
    if(!$check_stmt->fetch()) {
        $_SESSION['error'] = "Contrat non trouvé (ID: $id_contrat)";
        header('Location: index.php');
        exit();
    }

   
    $check_factures = "SELECT COUNT(*) FROM factures WHERE id_contrat = :id";
    $stmt_factures = $conn->prepare($check_factures);
    $stmt_factures->execute([':id' => $id_contrat]);
    $factures_count = $stmt_factures->fetchColumn();
    
   
    $conn->beginTransaction();
    
    
    if($factures_count > 0) {
        $delete_factures = "DELETE FROM factures WHERE id_contrat = :id";
        $stmt_delete_factures = $conn->prepare($delete_factures);
        $result_factures = $stmt_delete_factures->execute([':id' => $id_contrat]);
        
        if(!$result_factures) {
            throw new Exception("Erreur lors de la suppression des factures associées");
        }
    }

   
    $query = "DELETE FROM contrats WHERE id_contrat = :id";
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([':id' => $id_contrat]);

    if(!$result) {
        throw new Exception("Erreur lors de la suppression du contrat");
    }
    
    
    $conn->commit();
    $_SESSION['success'] = "Contrat #$id_contrat supprimé avec succès" . 
                          ($factures_count > 0 ? " (avec $factures_count facture(s) associée(s))" : "");

} catch(PDOException $e) {
   
    if(isset($conn)) $conn->rollBack();
    
    
    $error_code = $e->getCode();
    $error_message = $e->getMessage();
    
    if($error_code == '23000') {
        
        $_SESSION['error'] = "Impossible de supprimer ce contrat car il est référencé par d'autres tables. " . 
                            "Code: $error_code, Message: $error_message";
    } else {
        $_SESSION['error'] = "Erreur PDO lors de la suppression: Code: $error_code, Message: $error_message";
    }
    
} catch(Exception $e) {
    
    if(isset($conn)) $conn->rollBack();
    $_SESSION['error'] = "Erreur lors de la suppression: " . $e->getMessage();
}


header('Location: index.php');
exit();
?>
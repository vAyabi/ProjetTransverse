<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../config/Database.php';


class Notifications {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function create($type, $message, $destination_id, $destination_type, $source_id = null, $source_type = null) {
        error_log("Notification: $type - $message - Pour: $destination_id ($destination_type)");
        return true;
    }
}

if(isset($_GET['id'])) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $notifications = new Notifications($conn);

        // Vérifier que l'événement appartient à l'entreprise
        $stmt = $conn->prepare("SELECT * FROM evenements WHERE id_evenement = ? AND id_entreprise = ?");
        $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
        $event = $stmt->fetch();

        if(!$event) {
            throw new Exception("Événement non trouvé");
        }

        // Vérifier si l'événement est en cours (pas terminé ni programmé pour le futur)
        $now = time();
        $date_debut = strtotime($event['date_debut']);
        $date_fin = strtotime($event['date_fin']);
        
        // Permettre la suppression si l'événement est terminé (date_fin < now) ou pas encore commencé (date_debut > now)
        // Empêcher uniquement si l'événement est actuellement en cours
        if($date_debut <= $now && $date_fin >= $now && $event['statut'] === 'en_cours') {
            throw new Exception("Impossible de supprimer un événement qui est actuellement en cours");
        }

        // Supprimer les inscriptions
        $stmt = $conn->prepare("DELETE FROM inscriptions_evenements WHERE id_evenement = ?");
        $stmt->execute([$_GET['id']]);

        // Supprimer l'événement
        $stmt = $conn->prepare("DELETE FROM evenements WHERE id_evenement = ?");
        $result = $stmt->execute([$_GET['id']]);

        if($result) {
            // Notification au prestataire
            $notifications->create(
                'evenement_supprime',
                'L\'événement "' . $event['titre'] . '" a été supprimé',
                $event['id_prestataire'],
                'prestataires'
            );

            $_SESSION['success'] = "Événement supprimé avec succès";
        } else {
            throw new Exception("Erreur lors de la suppression");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: ../evenements.php');
exit();
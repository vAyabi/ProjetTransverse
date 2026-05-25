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

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        $notifications = new Notifications($conn);

        // Vérifier que les dates sont cohérentes
        $date_debut = strtotime($_POST['date_debut']);
        $date_fin = strtotime($_POST['date_fin']);
        
        if($date_fin <= $date_debut) {
            throw new Exception("La date de fin doit être après la date de début");
        }

        // Vérifier que le prestataire existe et est validé
        $stmt = $conn->prepare("SELECT id_prestataire FROM prestataires WHERE id_prestataire = ? AND statut_validation = 'validé'");
        $stmt->execute([$_POST['id_prestataire']]);
        if(!$stmt->fetch()) {
            throw new Exception("Prestataire non trouvé ou non validé");
        }

        $query = "INSERT INTO evenements (titre, description, type_evenement, date_debut, date_fin, 
                                        capacite_max, statut, id_prestataire, id_entreprise) 
                  VALUES (:titre, :description, :type_evenement, :date_debut, :date_fin, 
                          :capacite_max, 'programmé', :id_prestataire, :id_entreprise)";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':titre' => $_POST['titre'],
            ':description' => $_POST['description'],
            ':type_evenement' => $_POST['type_evenement'],
            ':date_debut' => $_POST['date_debut'],
            ':date_fin' => $_POST['date_fin'],
            ':capacite_max' => !empty($_POST['capacite_max']) ? $_POST['capacite_max'] : null,
            ':id_prestataire' => $_POST['id_prestataire'],
            ':id_entreprise' => $_SESSION['user_id']
        ]);

        if($result) {
            // Notification pour l'entreprise
            $notifications->create(
                'nouvel_evenement',
                'Nouvel événement créé : ' . htmlspecialchars($_POST['titre']),
                $_SESSION['user_id'],
                'entreprises'
            );

            // Notification pour le prestataire
            $notifications->create(
                'nouvel_evenement',
                'Vous avez été assigné à un nouvel événement : ' . htmlspecialchars($_POST['titre']),
                $_POST['id_prestataire'],
                'prestataires'
            );

            $_SESSION['success'] = "Événement créé avec succès";
        } else {
            throw new Exception("Erreur lors de la création de l'événement");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

header('Location: ../evenements.php');
exit();
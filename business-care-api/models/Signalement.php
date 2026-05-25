<?php
class Signalement {
    private $conn;
    private $table = 'signalements';

    // Propriétés du signalement
    public $id_signalement;
    public $contenu;
    public $statut;
    public $date_signalement;
    public $id_salarie;
    public $type;
    public $urgence;
    public $anonyme;
    
    // Propriétés étendues pour les jointures
    public $nom_salarie;
    public $nom_entreprise;
    public $id_entreprise;
    public $email_salarie;

    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer tous les signalements
    public function getAllSignalements() {
        $query = "
            SELECT s.*, 
                   sal.nom as nom_salarie, 
                   sal.email as email_salarie,
                   ent.nom as nom_entreprise,
                   sal.id_entreprise
            FROM " . $this->table . " s
            JOIN salaries sal ON s.id_salarie = sal.id_salarie
            JOIN entreprises ent ON sal.id_entreprise = ent.id_entreprise
            ORDER BY s.date_signalement DESC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt;
    }

    // Récupérer un signalement par ID
    public function getById($id) {
        $query = "
            SELECT s.*, 
                   sal.nom as nom_salarie, 
                   sal.email as email_salarie,
                   ent.nom as nom_entreprise,
                   sal.id_entreprise
            FROM " . $this->table . " s
            JOIN salaries sal ON s.id_salarie = sal.id_salarie
            JOIN entreprises ent ON sal.id_entreprise = ent.id_entreprise
            WHERE s.id_signalement = ?
            LIMIT 1
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id_signalement = $row['id_signalement'];
            $this->contenu = $row['contenu'];
            $this->statut = $row['statut'];
            $this->date_signalement = $row['date_signalement'];
            $this->id_salarie = $row['id_salarie'];
            $this->type = $row['type'];
            $this->urgence = $row['urgence'];
            $this->anonyme = $row['anonyme'];
            $this->nom_salarie = $row['nom_salarie'];
            $this->email_salarie = $row['email_salarie'];
            $this->nom_entreprise = $row['nom_entreprise'];
            $this->id_entreprise = $row['id_entreprise'];
            
            return true;
        }

        return false;
    }

    // Mettre à jour le statut d'un signalement
    public function updateStatus() {
        $query = "
            UPDATE " . $this->table . "
            SET statut = :statut
            WHERE id_signalement = :id_signalement
        ";

        $stmt = $this->conn->prepare($query);
        
        // Sécuriser les données
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->id_signalement = htmlspecialchars(strip_tags($this->id_signalement));

        // Liaison des paramètres
        $stmt->bindParam(':statut', $this->statut);
        $stmt->bindParam(':id_signalement', $this->id_signalement);

        // Exécuter la requête
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Ajouter une réponse à un signalement
    public function addResponse($contenu) {
        $query = "
            INSERT INTO signalements_reponses
                (id_signalement, contenu, date_reponse)
            VALUES
                (:id_signalement, :contenu, CURRENT_TIMESTAMP)
        ";

        $stmt = $this->conn->prepare($query);
        
        // Sécuriser les données
        $contenu = htmlspecialchars(strip_tags($contenu));
        $this->id_signalement = htmlspecialchars(strip_tags($this->id_signalement));

        // Liaison des paramètres
        $stmt->bindParam(':id_signalement', $this->id_signalement);
        $stmt->bindParam(':contenu', $contenu);

        // Exécuter la requête
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }
    
    // Récupérer les réponses à un signalement
    public function getResponses() {
        $query = "
            SELECT *
            FROM signalements_reponses
            WHERE id_signalement = ?
            ORDER BY date_reponse ASC
        ";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_signalement);
        $stmt->execute();

        return $stmt;
    }
}
?>
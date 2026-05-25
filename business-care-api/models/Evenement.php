<?php
class Evenement {
    private $conn;
    private $table_name = "evenements";
    
    // Propriétés
    public $id_evenement;
    public $titre;
    public $description;
    public $type_evenement;
    public $date_debut;
    public $date_fin;
    public $capacite_max;
    public $statut;
    public $created_at;
    public $id_prestataire;
    public $id_entreprise;
    
    // Propriétés jointes
    public $prestataire_nom;
    public $entreprise_nom;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les événements
    public function findAll() {
        $query = "SELECT e.*, p.nom as prestataire_nom, ent.nom as entreprise_nom
                  FROM " . $this->table_name . " e
                  LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
                  LEFT JOIN entreprises ent ON e.id_entreprise = ent.id_entreprise
                  ORDER BY e.date_debut ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les événements d'une entreprise
    public function findByEntreprise() {
        $query = "SELECT e.*, p.nom as prestataire_nom
                  FROM " . $this->table_name . " e
                  LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
                  WHERE e.id_entreprise = ?
                  ORDER BY e.date_debut ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_entreprise);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les événements d'un prestataire
    public function findByPrestataire() {
        $query = "SELECT e.*, ent.nom as entreprise_nom
                  FROM " . $this->table_name . " e
                  LEFT JOIN entreprises ent ON e.id_entreprise = ent.id_entreprise
                  WHERE e.id_prestataire = ?
                  ORDER BY e.date_debut ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_prestataire);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un événement
    public function findOne() {
        $query = "SELECT e.*, p.nom as prestataire_nom, ent.nom as entreprise_nom
                  FROM " . $this->table_name . " e
                  LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
                  LEFT JOIN entreprises ent ON e.id_entreprise = ent.id_entreprise
                  WHERE e.id_evenement = ?
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_evenement);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->titre = $row['titre'];
            $this->description = $row['description'];
            $this->type_evenement = $row['type_evenement'];
            $this->date_debut = $row['date_debut'];
            $this->date_fin = $row['date_fin'];
            $this->capacite_max = $row['capacite_max'];
            $this->statut = $row['statut'];
            $this->created_at = $row['created_at'];
            $this->id_prestataire = $row['id_prestataire'];
            $this->id_entreprise = $row['id_entreprise'];
            $this->prestataire_nom = $row['prestataire_nom'];
            $this->entreprise_nom = $row['entreprise_nom'];
            return true;
        }
        return false;
    }
    
    // Créer un événement
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (titre, description, type_evenement, date_debut, date_fin, capacite_max, statut, id_prestataire, id_entreprise) 
                 VALUES
                 (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type_evenement = htmlspecialchars(strip_tags($this->type_evenement));
        $this->date_debut = htmlspecialchars(strip_tags($this->date_debut));
        $this->date_fin = htmlspecialchars(strip_tags($this->date_fin));
        $this->capacite_max = htmlspecialchars(strip_tags($this->capacite_max));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->titre);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->type_evenement);
        $stmt->bindParam(4, $this->date_debut);
        $stmt->bindParam(5, $this->date_fin);
        $stmt->bindParam(6, $this->capacite_max);
        $stmt->bindParam(7, $this->statut);
        $stmt->bindParam(8, $this->id_prestataire);
        $stmt->bindParam(9, $this->id_entreprise);
        
        return $stmt->execute();
    }
    
    // Mettre à jour un événement
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET titre = ?, description = ?, type_evenement = ?, date_debut = ?, date_fin = ?, 
                 capacite_max = ?, statut = ?, id_prestataire = ? 
                 WHERE id_evenement = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->titre = htmlspecialchars(strip_tags($this->titre));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->type_evenement = htmlspecialchars(strip_tags($this->type_evenement));
        $this->date_debut = htmlspecialchars(strip_tags($this->date_debut));
        $this->date_fin = htmlspecialchars(strip_tags($this->date_fin));
        $this->capacite_max = htmlspecialchars(strip_tags($this->capacite_max));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->titre);
        $stmt->bindParam(2, $this->description);
        $stmt->bindParam(3, $this->type_evenement);
        $stmt->bindParam(4, $this->date_debut);
        $stmt->bindParam(5, $this->date_fin);
        $stmt->bindParam(6, $this->capacite_max);
        $stmt->bindParam(7, $this->statut);
        $stmt->bindParam(8, $this->id_prestataire);
        $stmt->bindParam(9, $this->id_evenement);
        
        return $stmt->execute();
    }
    
    // Supprimer un événement
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_evenement = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_evenement);
        return $stmt->execute();
    }
    
    // Obtenir les inscriptions à un événement
    public function getInscriptions() {
        $query = "SELECT i.*, s.nom, s.email
                  FROM inscriptions_evenements i
                  JOIN salaries s ON i.id_salarie = s.id_salarie
                  WHERE i.id_evenement = ?
                  ORDER BY i.date_inscription ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_evenement);
        $stmt->execute();
        return $stmt;
    }
    
    // Vérifier si un salarié est inscrit à l'événement
    public function isSalarieInscrit($id_salarie) {
        $query = "SELECT * FROM inscriptions_evenements 
                  WHERE id_evenement = ? AND id_salarie = ? AND statut = 'inscrit'
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_evenement);
        $stmt->bindParam(2, $id_salarie);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }
    
    // Inscrire un salarié à l'événement
    public function inscrireSalarie($id_salarie) {
        if ($this->isSalarieInscrit($id_salarie)) {
            return false; // Déjà inscrit
        }
        
        $query = "INSERT INTO inscriptions_evenements (id_salarie, id_evenement, statut) 
                  VALUES (?, ?, 'inscrit')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_salarie);
        $stmt->bindParam(2, $this->id_evenement);
        
        return $stmt->execute();
    }
    
    // Désinscrire un salarié de l'événement
    public function desinscrireSalarie($id_salarie) {
        $query = "UPDATE inscriptions_evenements 
                  SET statut = 'annulé' 
                  WHERE id_evenement = ? AND id_salarie = ? AND statut = 'inscrit'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_evenement);
        $stmt->bindParam(2, $id_salarie);
        
        return $stmt->execute();
    }
}
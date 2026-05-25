<?php
class Devis {
    // Connexion à la base de données
    private $conn;
    private $table = "devis";

    // Propriétés
    public $id_devis;
    public $id_entreprise;
    public $montant_total;
    public $validite_jours;
    public $statut;
    public $created_at;
    
    // Propriétés pour les jointures
    public $entreprise_nom;

    // Constructeur
    public function __construct($db) {
        $this->conn = $db;
    }

    // Récupérer tous les devis
    public function findAll() {
        $query = "SELECT d.*, e.nom as entreprise_nom
                 FROM " . $this->table . " d
                 LEFT JOIN entreprises e ON d.id_entreprise = e.id_entreprise
                 ORDER BY d.created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }

    // Récupérer un devis par son ID
    public function findOne() {
        $query = "SELECT d.*, e.nom as entreprise_nom
                 FROM " . $this->table . " d
                 LEFT JOIN entreprises e ON d.id_entreprise = e.id_entreprise
                 WHERE d.id_devis = ?
                 LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_devis);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id_entreprise = $row['id_entreprise'];
            $this->entreprise_nom = $row['entreprise_nom'];
            $this->montant_total = $row['montant_total'];
            $this->validite_jours = $row['validite_jours'];
            $this->statut = $row['statut'];
            $this->created_at = $row['created_at'];
            return true;
        }
        
        return false;
    }

    // Créer un nouveau devis
    public function create() {
        $query = "INSERT INTO " . $this->table . "
                (id_entreprise, montant_total, validite_jours, statut)
                VALUES
                (:id_entreprise, :montant_total, :validite_jours, :statut)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer et sécuriser les données
        $this->id_entreprise = htmlspecialchars(strip_tags($this->id_entreprise));
        $this->montant_total = htmlspecialchars(strip_tags($this->montant_total));
        $this->validite_jours = htmlspecialchars(strip_tags($this->validite_jours));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(':id_entreprise', $this->id_entreprise);
        $stmt->bindParam(':montant_total', $this->montant_total);
        $stmt->bindParam(':validite_jours', $this->validite_jours);
        $stmt->bindParam(':statut', $this->statut);
        
        // Exécuter la requête
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Mettre à jour un devis
    public function update() {
        $query = "UPDATE " . $this->table . "
                SET
                    id_entreprise = :id_entreprise,
                    montant_total = :montant_total,
                    validite_jours = :validite_jours,
                    statut = :statut
                WHERE
                    id_devis = :id_devis";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer et sécuriser les données
        $this->id_devis = htmlspecialchars(strip_tags($this->id_devis));
        $this->id_entreprise = htmlspecialchars(strip_tags($this->id_entreprise));
        $this->montant_total = htmlspecialchars(strip_tags($this->montant_total));
        $this->validite_jours = htmlspecialchars(strip_tags($this->validite_jours));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(':id_devis', $this->id_devis);
        $stmt->bindParam(':id_entreprise', $this->id_entreprise);
        $stmt->bindParam(':montant_total', $this->montant_total);
        $stmt->bindParam(':validite_jours', $this->validite_jours);
        $stmt->bindParam(':statut', $this->statut);
        
        // Exécuter la requête
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }

    // Supprimer un devis
    public function delete() {
        $query = "DELETE FROM " . $this->table . " WHERE id_devis = :id_devis";
        
        $stmt = $this->conn->prepare($query);
        
        $this->id_devis = htmlspecialchars(strip_tags($this->id_devis));
        
        $stmt->bindParam(':id_devis', $this->id_devis);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
<?php
class Prestataire {
    private $conn;
    private $table_name = "prestataires";
    
    // Propriétés
    public $id_prestataire;
    public $nom;
    public $specialite;
    public $email;
    public $password;
    public $telephone;
    public $rib;
    public $type_prestation;
    public $tarif_horaire;
    public $statut_validation;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les prestataires
    public function findAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les prestataires par spécialité
    public function findBySpecialite() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE specialite = ? ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->specialite);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un prestataire
    public function findOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_prestataire = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_prestataire);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom = $row['nom'];
            $this->specialite = $row['specialite'];
            $this->email = $row['email'];
            $this->telephone = $row['telephone'];
            $this->rib = $row['rib'];
            $this->type_prestation = $row['type_prestation'];
            $this->tarif_horaire = $row['tarif_horaire'];
            $this->statut_validation = $row['statut_validation'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    // Créer un prestataire
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nom, specialite, email, password, telephone, rib, type_prestation, tarif_horaire, statut_validation) 
                 VALUES
                 (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->specialite = htmlspecialchars(strip_tags($this->specialite));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = hash('sha256', $this->password);
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->rib = htmlspecialchars(strip_tags($this->rib));
        $this->type_prestation = htmlspecialchars(strip_tags($this->type_prestation));
        $this->tarif_horaire = htmlspecialchars(strip_tags($this->tarif_horaire));
        $this->statut_validation = htmlspecialchars(strip_tags($this->statut_validation));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->specialite);
        $stmt->bindParam(3, $this->email);
        $stmt->bindParam(4, $this->password);
        $stmt->bindParam(5, $this->telephone);
        $stmt->bindParam(6, $this->rib);
        $stmt->bindParam(7, $this->type_prestation);
        $stmt->bindParam(8, $this->tarif_horaire);
        $stmt->bindParam(9, $this->statut_validation);
        
        return $stmt->execute();
    }
    
    // Mettre à jour un prestataire
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nom = ?, specialite = ?, email = ?, telephone = ?, rib = ?, 
                 type_prestation = ?, tarif_horaire = ?, statut_validation = ? 
                 WHERE id_prestataire = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->specialite = htmlspecialchars(strip_tags($this->specialite));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->rib = htmlspecialchars(strip_tags($this->rib));
        $this->type_prestation = htmlspecialchars(strip_tags($this->type_prestation));
        $this->tarif_horaire = htmlspecialchars(strip_tags($this->tarif_horaire));
        $this->statut_validation = htmlspecialchars(strip_tags($this->statut_validation));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->specialite);
        $stmt->bindParam(3, $this->email);
        $stmt->bindParam(4, $this->telephone);
        $stmt->bindParam(5, $this->rib);
        $stmt->bindParam(6, $this->type_prestation);
        $stmt->bindParam(7, $this->tarif_horaire);
        $stmt->bindParam(8, $this->statut_validation);
        $stmt->bindParam(9, $this->id_prestataire);
        
        return $stmt->execute();
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password = ? WHERE id_prestataire = ?";
        $stmt = $this->conn->prepare($query);
        
        // Hasher le mot de passe
        $this->password = hash('sha256', $this->password);
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->password);
        $stmt->bindParam(2, $this->id_prestataire);
        
        return $stmt->execute();
    }
    
    // Supprimer un prestataire
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_prestataire = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_prestataire);
        return $stmt->execute();
    }
    
    // Vérifier si l'email existe déjà
    public function emailExists() {
        $query = "SELECT id_prestataire, password, statut_validation FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_prestataire = $row['id_prestataire'];
            $this->password = $row['password'];
            $this->statut_validation = $row['statut_validation'];
            return true;
        }
        return false;
    }
}
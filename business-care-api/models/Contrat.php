<?php
class Contrat {
    private $conn;
    private $table_name = "contrats";
    
    // Propriétés
    public $id_contrat;
    public $date_debut;
    public $date_fin;
    public $montant_total;
    public $type_paiement;
    public $statut;
    public $created_at;
    public $id_entreprise;
    
    // Propriétés jointes
    public $entreprise_nom;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les contrats
    public function findAll() {
        $query = "SELECT c.*, e.nom as entreprise_nom
                  FROM " . $this->table_name . " c
                  LEFT JOIN entreprises e ON c.id_entreprise = e.id_entreprise
                  ORDER BY c.date_debut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les contrats d'une entreprise
    public function findByEntreprise() {
        $query = "SELECT c.*
                  FROM " . $this->table_name . " c
                  WHERE c.id_entreprise = ?
                  ORDER BY c.date_debut DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_entreprise);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un contrat
    public function findOne() {
        $query = "SELECT c.*, e.nom as entreprise_nom
                  FROM " . $this->table_name . " c
                  LEFT JOIN entreprises e ON c.id_entreprise = e.id_entreprise
                  WHERE c.id_contrat = ?
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_contrat);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->date_debut = $row['date_debut'];
            $this->date_fin = $row['date_fin'];
            $this->montant_total = $row['montant_total'];
            $this->type_paiement = $row['type_paiement'];
            $this->statut = $row['statut'];
            $this->created_at = $row['created_at'];
            $this->id_entreprise = $row['id_entreprise'];
            $this->entreprise_nom = $row['entreprise_nom'];
            return true;
        }
        return false;
    }
    
    // Créer un contrat
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (date_debut, date_fin, montant_total, type_paiement, statut, id_entreprise) 
                 VALUES
                 (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->date_debut = htmlspecialchars(strip_tags($this->date_debut));
        $this->date_fin = htmlspecialchars(strip_tags($this->date_fin));
        $this->type_paiement = htmlspecialchars(strip_tags($this->type_paiement));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->date_debut);
        $stmt->bindParam(2, $this->date_fin);
        $stmt->bindParam(3, $this->montant_total);
        $stmt->bindParam(4, $this->type_paiement);
        $stmt->bindParam(5, $this->statut);
        $stmt->bindParam(6, $this->id_entreprise);
        
        // Si l'insertion réussit et qu'on utilise un paiement mensuel, créer aussi une facture
        if ($stmt->execute()) {
            $id_contrat = $this->conn->lastInsertId();
            
            if ($this->type_paiement === 'mensuel') {
                $query = "INSERT INTO factures (montant_total, date_echeance, statut, id_entreprise, id_contrat)
                          VALUES (?, ?, 'en_attente', ?, ?)";
                $stmt = $this->conn->prepare($query);
                $date_echeance = $this->date_debut; // Première facture à la date de début
                
                $stmt->bindParam(1, $this->montant_total);
                $stmt->bindParam(2, $date_echeance);
                $stmt->bindParam(3, $this->id_entreprise);
                $stmt->bindParam(4, $id_contrat);
                
                $stmt->execute();
            }
            
            return true;
        }
        
        return false;
    }
    
    // Mettre à jour un contrat
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET date_debut = ?, date_fin = ?, montant_total = ?, type_paiement = ?, statut = ? 
                 WHERE id_contrat = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->date_debut = htmlspecialchars(strip_tags($this->date_debut));
        $this->date_fin = htmlspecialchars(strip_tags($this->date_fin));
        $this->type_paiement = htmlspecialchars(strip_tags($this->type_paiement));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->date_debut);
        $stmt->bindParam(2, $this->date_fin);
        $stmt->bindParam(3, $this->montant_total);
        $stmt->bindParam(4, $this->type_paiement);
        $stmt->bindParam(5, $this->statut);
        $stmt->bindParam(6, $this->id_contrat);
        
        return $stmt->execute();
    }
    
    // Supprimer un contrat
    public function delete() {
        // D'abord, supprimer les factures associées
        $query = "DELETE FROM factures WHERE id_contrat = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_contrat);
        $stmt->execute();
        
        // Ensuite, supprimer le contrat
        $query = "DELETE FROM " . $this->table_name . " WHERE id_contrat = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_contrat);
        return $stmt->execute();
    }
}
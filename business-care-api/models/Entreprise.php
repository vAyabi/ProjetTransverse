<?php
class Entreprise {
    private $conn;
    private $table_name = "entreprises";
    
    // Propriétés
    public $id_entreprise;
    public $nom;
    public $siret;
    public $email;
    public $password;
    public $telephone;
    public $adresse;
    public $code_entreprise;
    public $type_formule;
    public $statut;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Vérifier si l'email existe déjà
    public function emailExists() {
        $query = "SELECT id_entreprise FROM " . $this->table_name . " 
                  WHERE email = :email LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        return ($stmt->rowCount() > 0);
    }
    
    // Créer une entreprise
    public function create() {
        // Générer un code entreprise unique
        $this->code_entreprise = $this->generateCodeEntreprise();
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, siret, email, password, telephone, adresse, code_entreprise, type_formule, statut) 
                  VALUES 
                  (:nom, :siret, :email, :password, :telephone, :adresse, :code_entreprise, :type_formule, :statut)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->siret = htmlspecialchars(strip_tags($this->siret));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Hasher le mot de passe
        $hashed_password = hash('sha256', $this->password);
        
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->type_formule = htmlspecialchars(strip_tags($this->type_formule));
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':siret', $this->siret);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':adresse', $this->adresse);
        $stmt->bindParam(':code_entreprise', $this->code_entreprise);
        $stmt->bindParam(':type_formule', $this->type_formule);
        $stmt->bindParam(':statut', $this->statut);
        
        if ($stmt->execute()) {
            $this->id_entreprise = $this->conn->lastInsertId();
            return true;
        }
        
        return false;
    }
    
    // Générer un code entreprise unique
    private function generateCodeEntreprise() {
        $prefix = strtoupper(substr($this->nom, 0, 3));
        $uniqueId = strtoupper(bin2hex(random_bytes(6)));
        return $prefix . $uniqueId;
    }
    
    // Lire toutes les entreprises
    public function findAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire une entreprise
    public function findOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_entreprise = :id LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_entreprise);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom = $row['nom'];
            $this->siret = $row['siret'];
            $this->email = $row['email'];
            $this->telephone = $row['telephone'];
            $this->adresse = $row['adresse'];
            $this->code_entreprise = $row['code_entreprise'];
            $this->type_formule = $row['type_formule'];
            $this->statut = $row['statut'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    // Mettre à jour une entreprise
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom = :nom, siret = :siret, email = :email, telephone = :telephone, 
                      adresse = :adresse, type_formule = :type_formule, statut = :statut 
                  WHERE id_entreprise = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->siret = htmlspecialchars(strip_tags($this->siret));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->telephone = htmlspecialchars(strip_tags($this->telephone));
        $this->adresse = htmlspecialchars(strip_tags($this->adresse));
        $this->type_formule = htmlspecialchars(strip_tags($this->type_formule));
        
        // Lier les paramètres
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':siret', $this->siret);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':telephone', $this->telephone);
        $stmt->bindParam(':adresse', $this->adresse);
        $stmt->bindParam(':type_formule', $this->type_formule);
        $stmt->bindParam(':statut', $this->statut);
        $stmt->bindParam(':id', $this->id_entreprise);
        
        return $stmt->execute();
    }
    
    // Supprimer une entreprise
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_entreprise = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_entreprise);
        return $stmt->execute();
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword($newPassword) {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password 
                  WHERE id_entreprise = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Hasher le nouveau mot de passe
        $hashed_password = hash('sha256', $newPassword);
        
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':id', $this->id_entreprise);
        
        return $stmt->execute();
    }
    
    // Méthode pour l'authentification
    public function login() {
        $query = "SELECT id_entreprise, nom, password, statut, type_formule, code_entreprise 
                  FROM " . $this->table_name . " 
                  WHERE email = :email AND statut = 1 
                  LIMIT 0,1";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Vérifier le mot de passe
            if (hash('sha256', $this->password) === $row['password']) {
                $this->id_entreprise = $row['id_entreprise'];
                $this->nom = $row['nom'];
                $this->statut = $row['statut'];
                $this->type_formule = $row['type_formule'];
                $this->code_entreprise = $row['code_entreprise'];
                return true;
            }
        }
        
        return false;
    }
}
?>
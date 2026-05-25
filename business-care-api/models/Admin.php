<?php
class Admin {
    private $conn;
    private $table_name = "admin";
    
    // Propriétés
    public $id_admin;
    public $nom;
    public $email;
    public $password;
    public $role;
    public $permissions;
    public $created_at;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les admins
    public function findAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un admin
    public function findOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_admin = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_admin);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom = $row['nom'];
            $this->email = $row['email'];
            $this->role = $row['role'];
            $this->permissions = $row['permissions'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }
    
    // Créer un admin
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nom, email, password, role, permissions) 
                 VALUES
                 (?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        
        // Hashage avec le sel personnalisé
        $salt = 'IF7EFECFGC%SDH';
        $password_salt = $this->password . $salt;
        $this->password = hash('sha256', $password_salt);
        
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->permissions = htmlspecialchars(strip_tags($this->permissions));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $this->password);
        $stmt->bindParam(4, $this->role);
        $stmt->bindParam(5, $this->permissions);
        
        return $stmt->execute();
    }
    
    // Mettre à jour un admin
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nom = ?, email = ?, role = ?, permissions = ? 
                 WHERE id_admin = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->role = htmlspecialchars(strip_tags($this->role));
        $this->permissions = htmlspecialchars(strip_tags($this->permissions));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $this->role);
        $stmt->bindParam(4, $this->permissions);
        $stmt->bindParam(5, $this->id_admin);
        
        return $stmt->execute();
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " 
                 SET password = ? 
                 WHERE id_admin = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Hashage avec le sel personnalisé
        $salt = 'IF7EFECFGC%SDH';
        $password_salt = $this->password . $salt;
        $this->password = hash('sha256', $password_salt);
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->password);
        $stmt->bindParam(2, $this->id_admin);
        
        return $stmt->execute();
    }
    
    // Supprimer un admin
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id_admin = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_admin);
        return $stmt->execute();
    }
    
    // Vérifier si l'email existe déjà
    public function emailExists() {
        $query = "SELECT id_admin, password FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_admin = $row['id_admin'];
            $this->password = $row['password'];
            return true;
        }
        return false;
    }
}
?>
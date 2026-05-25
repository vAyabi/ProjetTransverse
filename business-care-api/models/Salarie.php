<?php
class Salarie {
    private $conn;
    private $table_name = "salaries";
    
    // Propriétés
    public $id_salarie;
    public $nom;
    public $email;
    public $password;
    public $statut;
    public $first_login;
    public $created_at;
    public $id_entreprise;
    public $raison_archivage;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les salariés
    public function findAll() {
        $query = "SELECT s.*, e.nom as entreprise_nom 
                  FROM " . $this->table_name . " s
                  JOIN entreprises e ON s.id_entreprise = e.id_entreprise
                  ORDER BY s.nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les salariés d'une entreprise
    public function findByEntreprise() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_entreprise = ? ORDER BY nom ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_entreprise);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un salarié
    public function findOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_salarie = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_salarie);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom = $row['nom'];
            $this->email = $row['email'];
            $this->statut = $row['statut'];
            $this->first_login = $row['first_login'];
            $this->created_at = $row['created_at'];
            $this->id_entreprise = $row['id_entreprise'];
            return true;
        }
        return false;
    }
    
    // Créer un salarié
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 (nom, email, password, statut, first_login, id_entreprise) 
                 VALUES
                 (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = hash('sha256', $this->password);
        $this->statut = $this->statut ? 1 : 0;
        $this->first_login = $this->first_login ? 1 : 0;
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $this->password);
        $stmt->bindParam(4, $this->statut);
        $stmt->bindParam(5, $this->first_login);
        $stmt->bindParam(6, $this->id_entreprise);
        
        return $stmt->execute();
    }
    
    // Mettre à jour un salarié
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET nom = ?, email = ?, statut = ?, first_login = ? 
                 WHERE id_salarie = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->nom = htmlspecialchars(strip_tags($this->nom));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->statut = $this->statut ? 1 : 0;
        $this->first_login = $this->first_login ? 1 : 0;
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->nom);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $this->statut);
        $stmt->bindParam(4, $this->first_login);
        $stmt->bindParam(5, $this->id_salarie);
        
        return $stmt->execute();
    }
    
    // Mettre à jour le mot de passe
    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " 
                 SET password = ?, first_login = 0 
                 WHERE id_salarie = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Hasher le mot de passe
        $this->password = hash('sha256', $this->password);
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->password);
        $stmt->bindParam(2, $this->id_salarie);
        
        return $stmt->execute();
    }
    
    // Archiver un salarié individuel (version corrigée)
    public function archiver() {
        error_log("Début de l'archivage pour le salarié ID: " . $this->id_salarie);
        
        // Démarrer une transaction
        $this->conn->beginTransaction();
        
        try {
            // 1. Récupérer les informations complètes du salarié
            if (!$this->nom) {
                $this->findOne();
            }
            
            // 2. Vérifier les références dans d'autres tables
            $stmt = $this->conn->prepare("
                SELECT 
                    (SELECT COUNT(*) FROM inscriptions_evenements WHERE id_salarie = ?) as inscriptions,
                    (SELECT COUNT(*) FROM rendez_vous_medicaux WHERE id_salarie = ?) as rdv,
                    (SELECT COUNT(*) FROM signalements WHERE id_salarie = ?) as signalements,
                    (SELECT COUNT(*) FROM communautes_membres WHERE id_salarie = ?) as communautes,
                    (SELECT COUNT(*) FROM participations_associations WHERE id_salarie = ?) as associations,
                    (SELECT COUNT(*) FROM quota_chatbot WHERE id_salarie = ?) as chatbot,
                    (SELECT COUNT(*) FROM quota_rdv_medicaux WHERE id_salarie = ?) as quota_rdv
            ");
            
            $stmt->execute([$this->id_salarie, $this->id_salarie, $this->id_salarie, 
                           $this->id_salarie, $this->id_salarie, $this->id_salarie,
                           $this->id_salarie]);
            
            $references = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Références trouvées: " . json_encode($references));
            
            // 3. Supprimer les références si elles existent
            if ($references['inscriptions'] > 0) {
                $stmt = $this->conn->prepare("DELETE FROM inscriptions_evenements WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                error_log("Suppression de " . $references['inscriptions'] . " inscriptions");
            }
            
            if ($references['rdv'] > 0) {
                $stmt = $this->conn->prepare("DELETE FROM rendez_vous_medicaux WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                error_log("Suppression de " . $references['rdv'] . " rendez-vous");
            }
            
            if ($references['signalements'] > 0) {
                // D'abord supprimer les réponses aux signalements
                $stmt = $this->conn->prepare("
                    DELETE sr FROM signalements_reponses sr 
                    JOIN signalements s ON sr.id_signalement = s.id_signalement 
                    WHERE s.id_salarie = ?
                ");
                $stmt->execute([$this->id_salarie]);
                
                $stmt = $this->conn->prepare("DELETE FROM signalements WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                error_log("Suppression de " . $references['signalements'] . " signalements");
            }
            
            if ($references['communautes'] > 0) {
                // D'abord supprimer les signalements communautaires (s'il y en a)
                $stmt = $this->conn->prepare("DELETE FROM communautes_signalements WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les commentaires
                $stmt = $this->conn->prepare("DELETE FROM communautes_commentaires WHERE id_auteur = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les publications
                $stmt = $this->conn->prepare("DELETE FROM communautes_publications WHERE id_auteur = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les participations aux événements
                $stmt = $this->conn->prepare("DELETE FROM communautes_participants WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les événements créés
                $stmt = $this->conn->prepare("DELETE FROM communautes_evenements WHERE id_createur = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les appartenances aux communautés
                $stmt = $this->conn->prepare("DELETE FROM communautes_membres WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                
                // Supprimer les communautés créées
                $stmt = $this->conn->prepare("DELETE FROM communautes WHERE id_createur = ?");
                $stmt->execute([$this->id_salarie]);
                
                error_log("Suppression des données communautés");
            }
            
            if ($references['associations'] > 0) {
                // D'abord supprimer les détails de participation
                $stmt = $this->conn->prepare("
                    DELETE pd FROM participation_details pd 
                    JOIN participations_associations pa ON pd.id_participation = pa.id_participation 
                    WHERE pa.id_salarie = ?
                ");
                $stmt->execute([$this->id_salarie]);
                
                $stmt = $this->conn->prepare("DELETE FROM participations_associations WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                error_log("Suppression de " . $references['associations'] . " participations");
            }
            
            if ($references['chatbot'] > 0) {
                $stmt = $this->conn->prepare("DELETE FROM quota_chatbot WHERE id_salarie = ?");
                $stmt->execute([$this->id_salarie]);
                error_log("Suppression du quota chatbot");
            }
            
            // Supprimer les références dans quota_rdv_medicaux
            $stmt = $this->conn->prepare("DELETE FROM quota_rdv_medicaux WHERE id_salarie = ?");
            $stmt->execute([$this->id_salarie]);
            error_log("Suppression du quota RDV médicaux");
            
            // 4. Insérer dans la table d'archives
            $query = "INSERT INTO salaries_archives 
                     (id_salarie_original, id_entreprise_original, nom, email, statut, raison_archivage) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                $this->id_salarie,
                $this->id_entreprise,
                $this->nom,
                $this->email,
                $this->statut,
                $this->raison_archivage ?: "Suppression manuelle"
            ]);
            
            error_log("Insertion dans les archives réussie");
            
            // 5. Supprimer le salarié
            $query = "DELETE FROM " . $this->table_name . " WHERE id_salarie = ?";
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([$this->id_salarie]);
            
            if (!$result) {
                throw new Exception("Impossible de supprimer le salarié");
            }
            
            error_log("Suppression du salarié réussie");
            
            // Valider la transaction
            $this->conn->commit();
            error_log("Transaction validée - Archivage complet");
            
            return true;
            
        } catch (Exception $e) {
            error_log("ERREUR dans archiver(): " . $e->getMessage());
            $this->conn->rollback();
            throw $e;
        }
    }
    
    // Archiver des salariés pour une entreprise spécifique
    public function archiverParEntreprise($id_entreprise, $raison_archivage = "Entreprise archivée") {
        // Récupérer tous les salariés de cette entreprise
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_entreprise = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_entreprise);
        $stmt->execute();
        
        $salaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Archiver chaque salarié
        foreach ($salaries as $salarie) {
            $this->id_salarie = $salarie['id_salarie'];
            $this->raison_archivage = $raison_archivage;
            $this->archiver();
        }
        
        return true;
    }
    
    // Récupérer tous les salariés archivés
    public function findAllArchived() {
        $query = "SELECT sa.*, e.nom as nom_entreprise 
                  FROM salaries_archives sa
                  LEFT JOIN entreprises e ON sa.id_entreprise_original = e.id_entreprise
                  ORDER BY sa.date_archivage DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Vérifier si l'email existe déjà
    public function emailExists() {
        $query = "SELECT id_salarie, password, id_entreprise, statut FROM " . $this->table_name . " WHERE email = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id_salarie = $row['id_salarie'];
            $this->password = $row['password'];
            $this->id_entreprise = $row['id_entreprise'];
            $this->statut = $row['statut'];
            return true;
        }
        return false;
    }
    
    // Remplacer la méthode delete par l'archivage
    public function delete() {
        if (empty($this->raison_archivage)) {
            $this->raison_archivage = "Suppression manuelle";
        }
        return $this->archiver();
    }
}
?>
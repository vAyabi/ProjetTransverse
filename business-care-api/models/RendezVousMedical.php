<?php
class RendezVousMedical {
    private $conn;
    private $table_name = "rendez_vous_medicaux";
    
    // Propriétés
    public $id_rdv;
    public $id_salarie;
    public $id_prestataire;
    public $date_heure;
    public $type;
    public $notes;
    public $statut;
    public $hors_quota;
    public $created_at;
    
    // Propriétés jointes
    public $salarie_nom;
    public $prestataire_nom;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Lire tous les rendez-vous
    public function findAll() {
        $query = "SELECT r.*, s.nom as salarie_nom, p.nom as prestataire_nom
                  FROM " . $this->table_name . " r
                  LEFT JOIN salaries s ON r.id_salarie = s.id_salarie
                  LEFT JOIN prestataires p ON r.id_prestataire = p.id_prestataire
                  ORDER BY r.date_heure ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les rendez-vous d'un salarié
    public function findBySalarie() {
        $query = "SELECT r.*, p.nom as prestataire_nom
                  FROM " . $this->table_name . " r
                  LEFT JOIN prestataires p ON r.id_prestataire = p.id_prestataire
                  WHERE r.id_salarie = ?
                  ORDER BY r.date_heure ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_salarie);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire les rendez-vous d'un prestataire
    public function findByPrestataire() {
        $query = "SELECT r.*, s.nom as salarie_nom
                  FROM " . $this->table_name . " r
                  LEFT JOIN salaries s ON r.id_salarie = s.id_salarie
                  WHERE r.id_prestataire = ?
                  ORDER BY r.date_heure ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_prestataire);
        $stmt->execute();
        return $stmt;
    }
    
    // Lire un rendez-vous
    public function findOne() {
        $query = "SELECT r.*, s.nom as salarie_nom, p.nom as prestataire_nom
                  FROM " . $this->table_name . " r
                  LEFT JOIN salaries s ON r.id_salarie = s.id_salarie
                  LEFT JOIN prestataires p ON r.id_prestataire = p.id_prestataire
                  WHERE r.id_rdv = ?
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rdv);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->id_salarie = $row['id_salarie'];
            $this->id_prestataire = $row['id_prestataire'];
            $this->date_heure = $row['date_heure'];
            $this->type = $row['type'];
            $this->notes = $row['notes'];
            $this->statut = $row['statut'];
            $this->hors_quota = $row['hors_quota'];
            $this->created_at = $row['created_at'];
            $this->salarie_nom = $row['salarie_nom'];
            $this->prestataire_nom = $row['prestataire_nom'];
            return true;
        }
        return false;
    }
    
    // Créer un rendez-vous
    public function create() {
        // D'abord, vérifier le quota du salarié si le RDV n'est pas marqué comme hors quota
        if (!$this->hors_quota) {
            $month = date('n', strtotime($this->date_heure));
            $year = date('Y', strtotime($this->date_heure));
            
            if (!$this->verifierQuota($this->id_salarie, $month, $year)) {
                return false; // Quota dépassé
            }
        }
        
        $query = "INSERT INTO " . $this->table_name . " 
                 (id_salarie, id_prestataire, date_heure, type, notes, statut, hors_quota) 
                 VALUES
                 (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        $this->hors_quota = $this->hors_quota ? 1 : 0;
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->id_salarie);
        $stmt->bindParam(2, $this->id_prestataire);
        $stmt->bindParam(3, $this->date_heure);
        $stmt->bindParam(4, $this->type);
        $stmt->bindParam(5, $this->notes);
        $stmt->bindParam(6, $this->statut);
        $stmt->bindParam(7, $this->hors_quota);
        
        
        if (!$this->hors_quota) {
                $this->decrementerQuota($this->id_salarie, $month, $year);
            }
            return true;
        }
        return false;
    }
    
    // Mettre à jour un rendez-vous
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                 SET date_heure = ?, type = ?, notes = ?, statut = ? 
                 WHERE id_rdv = ?";
        
        $stmt = $this->conn->prepare($query);
        
        // Nettoyer les données
        $this->notes = htmlspecialchars(strip_tags($this->notes));
        $this->type = htmlspecialchars(strip_tags($this->type));
        $this->statut = htmlspecialchars(strip_tags($this->statut));
        
        // Lier les paramètres
        $stmt->bindParam(1, $this->date_heure);
        $stmt->bindParam(2, $this->type);
        $stmt->bindParam(3, $this->notes);
        $stmt->bindParam(4, $this->statut);
        $stmt->bindParam(5, $this->id_rdv);
        
        return $stmt->execute();
    }
    
    // Supprimer un rendez-vous
    public function delete() {
        // D'abord, récupérer les informations du rendez-vous pour potentiellement réincrémenter le quota
        $query = "SELECT id_salarie, date_heure, hors_quota, statut FROM " . $this->table_name . " WHERE id_rdv = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id_rdv);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $id_salarie = $row['id_salarie'];
            $date_heure = $row['date_heure'];
            $hors_quota = $row['hors_quota'];
            $statut = $row['statut'];
            
            // Supprimer le rendez-vous
            $query = "DELETE FROM " . $this->table_name . " WHERE id_rdv = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id_rdv);
            
            if ($stmt->execute()) {
                // Si le rendez-vous était programmé et pas hors quota, réincrémenter le quota
                if ($statut == 'programmé' && !$hors_quota) {
                    $month = date('n', strtotime($date_heure));
                    $year = date('Y', strtotime($date_heure));
                    $this->incrementerQuota($id_salarie, $month, $year);
                }
                return true;
            }
        }
        
        return false;
    }
    
    // Vérifier si un salarié a encore du quota
    private function verifierQuota($id_salarie, $month, $year) {
        $query = "SELECT quota_disponible FROM quota_rdv_medicaux 
                  WHERE id_salarie = ? AND mois = ? AND annee = ?
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_salarie);
        $stmt->bindParam(2, $month);
        $stmt->bindParam(3, $year);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['quota_disponible'] > 0;
        }
        
        // Si pas de quota trouvé, vérifier le type de formule de l'entreprise du salarié
        $query = "SELECT e.type_formule 
                  FROM salaries s
                  JOIN entreprises e ON s.id_entreprise = e.id_entreprise
                  WHERE s.id_salarie = ?
                  LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_salarie);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $type_formule = $row['type_formule'];
            
            // Déterminer le quota en fonction du type de formule
            $quota = 0;
            switch ($type_formule) {
                case 'starter':
                    $quota = 1;
                    break;
                case 'basic':
                    $quota = 2;
                    break;
                case 'premium':
                    $quota = 3;
                    break;
            }
            
            // Créer une nouvelle entrée de quota
            $query = "INSERT INTO quota_rdv_medicaux (id_salarie, mois, annee, quota_disponible, quota_total)
                      VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $id_salarie);
            $stmt->bindParam(2, $month);
            $stmt->bindParam(3, $year);
            $stmt->bindParam(4, $quota);
            $stmt->bindParam(5, $quota);
            $stmt->execute();
            
            return $quota > 0;
        }
        
        return false;
    }
    
    // Décrémenter le quota d'un salarié
    private function decrementerQuota($id_salarie, $month, $year) {
        $query = "UPDATE quota_rdv_medicaux 
                  SET quota_disponible = quota_disponible - 1 
                  WHERE id_salarie = ? AND mois = ? AND annee = ? AND quota_disponible > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_salarie);
        $stmt->bindParam(2, $month);
        $stmt->bindParam(3, $year);
        return $stmt->execute();
    }
    
    // Incrémenter le quota d'un salarié
    private function incrementerQuota($id_salarie, $month, $year) {
        $query = "UPDATE quota_rdv_medicaux 
                  SET quota_disponible = quota_disponible + 1 
                  WHERE id_salarie = ? AND mois = ? AND annee = ? AND quota_disponible < quota_total";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_salarie);
        $stmt->bindParam(2, $month);
        $stmt->bindParam(3, $year);
        return $stmt->execute();
    }

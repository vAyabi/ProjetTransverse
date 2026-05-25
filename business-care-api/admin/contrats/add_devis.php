<?php
session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

require_once '../../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    
    $query = "SELECT id_entreprise, nom FROM entreprises WHERE statut = 1 ORDER BY nom";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $query = "INSERT INTO devis (id_entreprise, montant_total, validite_jours, statut) 
                  VALUES (:id_entreprise, :montant_total, :validite_jours, :statut)";
        
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':id_entreprise' => $_POST['id_entreprise'],
            ':montant_total' => $_POST['montant_total'],
            ':validite_jours' => $_POST['validite_jours'],
            ':statut' => 'en_attente'
        ]);

        if($result) {
            $_SESSION['success'] = "Devis ajouté avec succès";
            header('Location: index.php');
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de l'ajout du devis";
        }
    }
    
} catch(PDOException $e) {
    $_SESSION['error'] = "Erreur lors de l'ajout du devis: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Nouveau Devis</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="id_entreprise" class="form-label">Entreprise</label>
                            <select class="form-select" id="id_entreprise" name="id_entreprise" required>
                                <option value="">Sélectionner une entreprise</option>
                                <?php foreach($entreprises as $entreprise): ?>
                                    <option value="<?= $entreprise['id_entreprise'] ?>">
                                        <?= htmlspecialchars($entreprise['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="montant_total" class="form-label">Montant total</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="montant_total" 
                                           name="montant_total" step="0.01" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="validite_jours" class="form-label">Validité (en jours)</label>
                                <input type="number" class="form-control" id="validite_jours" 
                                       name="validite_jours" value="30" min="1" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
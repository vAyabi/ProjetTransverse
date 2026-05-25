<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les services du prestataire
$stmt = $conn->prepare("SELECT * FROM prestataires WHERE id_prestataire = ?");
$stmt->execute([$_SESSION['user_id']]);
$prestataire = $stmt->fetch();

include '../includes/header_dashboard.php';
?>

<div class="container mt-4">
    <h2>Gestion des services</h2>
    
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">Mes informations de service</h5>
            <form action="update_services.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Type de prestation</label>
                        <input type="text" class="form-control" name="type_prestation" 
                               value="<?= htmlspecialchars($prestataire['type_prestation']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Spécialité</label>
                        <select class="form-select" name="specialite" required>
                            <option value="medical" <?= $prestataire['specialite'] == 'medical' ? 'selected' : '' ?>>Médical</option>
                            <option value="bien-etre" <?= $prestataire['specialite'] == 'bien-etre' ? 'selected' : '' ?>>Bien-être</option>
                            <option value="sport" <?= $prestataire['specialite'] == 'sport' ? 'selected' : '' ?>>Sport</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Tarif horaire (€)</label>
                        <input type="number" step="0.01" class="form-control" name="tarif_horaire" 
                               value="<?= htmlspecialchars($prestataire['tarif_horaire']) ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Mettre à jour mes services</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
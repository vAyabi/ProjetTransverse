<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les infos du prestataire
$stmt = $conn->prepare("SELECT * FROM prestataires WHERE id_prestataire = ?");
$stmt->execute([$_SESSION['user_id']]);
$prestataire = $stmt->fetch();

include '../includes/header_dashboard.php';
?>

<div class="container mt-4">
    <h2>Tableau de bord - <?= htmlspecialchars($prestataire['nom']) ?></h2>
    
   
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Spécialité</h6>
                    <p class="h4"><?= ucfirst($prestataire['specialite']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Type de prestation</h6>
                    <p class="h4"><?= htmlspecialchars($prestataire['type_prestation']) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Tarif horaire</h6>
                    <p class="h4"><?= number_format($prestataire['tarif_horaire'], 2) ?> €/h</p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Actions rapides</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="planning.php" class="btn btn-primary">
                    <i class="fas fa-calendar"></i> Voir mon planning
                </a>
                <a href="services.php" class="btn btn-success">
                    <i class="fas fa-cog"></i> Gérer mes services
                </a>
                <a href="profil.php" class="btn btn-info">
                    <i class="fas fa-user"></i> Modifier mon profil
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
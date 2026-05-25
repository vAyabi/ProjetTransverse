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
    <h2>Mon Profil</h2>
    
    <div class="card">
        <div class="card-body">
            <form action="update_profil.php" method="POST">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom complet</label>
                        <input type="text" class="form-control" name="nom" 
                               value="<?= htmlspecialchars($prestataire['nom']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= htmlspecialchars($prestataire['email']) ?>" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone" 
                               value="<?= htmlspecialchars($prestataire['telephone']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">RIB</label>
                        <input type="text" class="form-control" name="rib" 
                               value="<?= htmlspecialchars($prestataire['rib']) ?>">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                    <input type="password" class="form-control" name="password">
                </div>

                <button type="submit" class="btn btn-primary">Mettre à jour mon profil</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
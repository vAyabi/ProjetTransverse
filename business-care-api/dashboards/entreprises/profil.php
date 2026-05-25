<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les infos de l'entreprise
$stmt = $conn->prepare("SELECT * FROM entreprises WHERE id_entreprise = ?");
$stmt->execute([$_SESSION['user_id']]);
$entreprise = $stmt->fetch();

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Profil de l'entreprise</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form action="profil_process.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nom de l'entreprise</label>
                            <input type="text" class="form-control" name="nom" 
                                   value="<?= htmlspecialchars($entreprise['nom']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SIRET</label>
                            <input type="text" class="form-control" name="siret" 
                                   value="<?= htmlspecialchars($entreprise['siret']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($entreprise['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" 
                                   value="<?= htmlspecialchars($entreprise['telephone']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Adresse</label>
                            <textarea class="form-control" name="adresse" rows="3"><?= htmlspecialchars($entreprise['adresse']) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" class="form-control" name="password">
                        </div>

                        <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
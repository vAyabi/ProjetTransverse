<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les salariés de l'entreprise
$stmt = $conn->prepare("SELECT * FROM salaries WHERE id_entreprise = ? ORDER BY nom");
$stmt->execute([$_SESSION['user_id']]);
$salaries = $stmt->fetchAll();

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des salariés</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addSalarieModal">
            <i class="fas fa-user-plus"></i> Ajouter un salarié
        </button>
    </div>

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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Date d'ajout</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($salaries): ?>
                            <?php foreach($salaries as $salarie): ?>
                                <tr>
                                    <td><?= htmlspecialchars($salarie['nom']) ?></td>
                                    <td><?= htmlspecialchars($salarie['email']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($salarie['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $salarie['statut'] ? 'success' : 'warning' ?>">
                                            <?= $salarie['statut'] ? 'Actif' : 'En attente' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if(!$salarie['statut']): ?>
                                                <button type="button" class="btn btn-sm btn-warning" 
                                                        onclick="resendInvitation(<?= $salarie['id_salarie'] ?>)">
                                                    <i class="fas fa-envelope"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editSalarieModal<?= $salarie['id_salarie'] ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteSalarie(<?= $salarie['id_salarie'] ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        
                                        <div class="modal fade" id="editSalarieModal<?= $salarie['id_salarie'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Modifier le salarié</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="salaries/edit.php" method="POST">
                                                        <input type="hidden" name="id_salarie" value="<?= $salarie['id_salarie'] ?>">
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Nom</label>
                                                                <input type="text" class="form-control" name="nom" 
                                                                       value="<?= htmlspecialchars($salarie['nom']) ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Email</label>
                                                                <input type="email" class="form-control" name="email" 
                                                                       value="<?= htmlspecialchars($salarie['email']) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                            <button type="submit" class="btn btn-primary">Sauvegarder</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucun salarié enregistré</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addSalarieModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Ajouter un salarié</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="salaries/add.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email professionnel</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Ajouter et envoyer l'invitation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteSalarie(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer ce salarié ?')) {
        window.location.href = 'salaries/delete.php?id=' + id;
    }
}

function resendInvitation(id) {
    if(confirm('Renvoyer l\'invitation à ce salarié ?')) {
        window.location.href = 'salaries/resend.php?id=' + id;
    }
}
</script>

<?php include '../includes/footer_dashboard.php'; ?>
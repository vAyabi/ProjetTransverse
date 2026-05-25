<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les signalements existants du salarié
$stmt = $conn->prepare("
    SELECT * FROM signalements 
    WHERE id_salarie = ? 
    ORDER BY date_signalement DESC
");
$stmt->execute([$_SESSION['user_id']]);
$signalements = $stmt->fetchAll();

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Signalements</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newSignalementModal">
            <i class="fas fa-plus"></i> Nouveau signalement
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

    <!-- Liste des signalements -->
    <div class="card">
        <div class="card-body">
            <?php if($signalements): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th width="40%">Description</th>
                                <th>Urgence</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($signalements as $signalement): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($signalement['date_signalement'])) ?></td>
                                    <td>
                                        <?php
                                        $type_badge = match($signalement['type']) {
                                            'harcelement' => 'bg-danger',
                                            'condition_travail' => 'bg-warning',
                                            'discrimination' => 'bg-info',
                                            'sante_securite' => 'bg-primary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $type_badge ?>">
                                            <?= ucfirst(str_replace('_', ' ', $signalement['type'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($signalement['contenu']) ?></td>
                                    <td>
                                        <?php
                                        $urgence_badge = match($signalement['urgence']) {
                                            'tres_urgent' => 'bg-danger',
                                            'urgent' => 'bg-warning',
                                            'moyen' => 'bg-info',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $urgence_badge ?>">
                                            <?= ucfirst(str_replace('_', ' ', $signalement['urgence'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_badge = match($signalement['statut']) {
                                            'nouveau' => 'bg-primary',
                                            'en_traitement' => 'bg-warning',
                                            'traité' => 'bg-success',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $status_badge ?>">
                                            <?= ucfirst($signalement['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <!-- Réponses/Suivi -->
                                <tr>
                                    <td colspan="5" class="border-0">
                                        <div class="accordion accordion-flush">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#reponse-<?= $signalement['id_signalement'] ?>">
                                                        Voir le suivi
                                                    </button>
                                                </h2>
                                                <div id="reponse-<?= $signalement['id_signalement'] ?>" class="accordion-collapse collapse">
                                                    <div class="accordion-body bg-light">
                                                        <?php
                                                        $stmt = $conn->prepare("SELECT * FROM signalements_reponses WHERE id_signalement = ? ORDER BY date_reponse ASC");
                                                        $stmt->execute([$signalement['id_signalement']]);
                                                        $reponses = $stmt->fetchAll();
                                                        
                                                        if($reponses): ?>
                                                            <?php foreach($reponses as $reponse): ?>
                                                                <div class="mb-2 p-2 border-bottom">
                                                                    <div class="text-muted small"><?= date('d/m/Y H:i', strtotime($reponse['date_reponse'])) ?></div>
                                                                    <div><?= htmlspecialchars($reponse['contenu']) ?></div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <p class="text-muted mb-0">Pas encore de réponse</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Aucun signalement effectué</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Nouveau Signalement -->
<div class="modal fade" id="newSignalementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouveau signalement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="signalement/add.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de signalement</label>
                        <select class="form-select" name="type" required>
                            <option value="harcelement">Harcèlement</option>
                            <option value="condition_travail">Conditions de travail</option>
                            <option value="discrimination">Discrimination</option>
                            <option value="sante_securite">Santé et sécurité</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Niveau d'urgence</label>
                        <select class="form-select" name="urgence" required>
                            <option value="faible">Faible</option>
                            <option value="moyen">Moyen</option>
                            <option value="urgent">Urgent</option>
                            <option value="tres_urgent">Très urgent</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description détaillée</label>
                        <textarea class="form-control" name="contenu" rows="5" required 
                                placeholder="Décrivez la situation de manière précise"></textarea>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="anonyme" id="anonyme">
                            <label class="form-check-label" for="anonyme">
                                Faire un signalement anonyme
                            </label>
                        </div>
                        <small class="text-muted">Votre identité sera masquée dans le traitement du signalement</small>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Votre signalement sera traité de manière confidentielle.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Envoyer le signalement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
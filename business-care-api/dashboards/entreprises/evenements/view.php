<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

if(!isset($_GET['id'])) {
    $_SESSION['error'] = "ID de l'événement non spécifié";
    header('Location: ../evenements.php');
    exit();
}


$stmt = $conn->prepare("
    SELECT e.*, p.nom as prestataire_nom, p.specialite, p.telephone
    FROM evenements e
    JOIN prestataires p ON e.id_prestataire = p.id_prestataire
    WHERE e.id_evenement = ? AND e.id_entreprise = ?
");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$event = $stmt->fetch();

if(!$event) {
    $_SESSION['error'] = "Événement non trouvé";
    header('Location: ../evenements.php');
    exit();
}


$stmt = $conn->prepare("
    SELECT s.*, ie.date_inscription, ie.statut as statut_inscription
    FROM inscriptions_evenements ie
    JOIN salaries s ON ie.id_salarie = s.id_salarie
    WHERE ie.id_evenement = ?
    ORDER BY ie.date_inscription DESC
");
$stmt->execute([$_GET['id']]);
$inscrits = $stmt->fetchAll();

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <?= htmlspecialchars($event['titre']) ?>
                <span class="badge bg-<?= $event['statut'] === 'programmé' ? 'success' : 
                                     ($event['statut'] === 'en_cours' ? 'primary' : 
                                     ($event['statut'] === 'terminé' ? 'secondary' : 'danger')) ?>">
                    <?= ucfirst($event['statut']) ?>
                </span>
            </h5>
            <div class="btn-group">
                <a href="edit.php?id=<?= $event['id_evenement'] ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> Modifier
                </a>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Informations générales</h6>
                    <p><strong>Date de début:</strong> <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?></p>
                    <p><strong>Date de fin:</strong> <?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?></p>
                    <p><strong>Type d'événement:</strong> <?= ucfirst($event['type_evenement']) ?></p>
                    <p><strong>Capacité maximale:</strong> <?= $event['capacite_max'] ? $event['capacite_max'] . ' personnes' : 'Illimitée' ?></p>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-muted mb-3">Prestataire</h6>
                    <p><strong>Nom:</strong> <?= htmlspecialchars($event['prestataire_nom']) ?></p>
                    <p><strong>Spécialité:</strong> <?= ucfirst($event['specialite']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($event['telephone']) ?></p>
                </div>
            </div>

            <?php if($event['description']): ?>
            <div class="mb-4">
                <h6 class="text-muted mb-3">Description</h6>
                <p class="mb-0"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
            </div>
            <?php endif; ?>

            <div>
                <h6 class="text-muted mb-3">Participants (<?= count($inscrits) ?>)</h6>
                <?php if(empty($inscrits)): ?>
                    <p class="text-center text-muted">Aucun participant inscrit</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Date d'inscription</th>
                                    <th>Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($inscrits as $inscrit): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($inscrit['nom']) ?></td>
                                        <td><?= htmlspecialchars($inscrit['email']) ?></td>
                                        <td><?= date('d/m/Y', strtotime($inscrit['date_inscription'])) ?></td>
                                        <td>
                                            <span class="badge bg-<?= $inscrit['statut_inscription'] === 'présent' ? 'success' : 
                                                                  ($inscrit['statut_inscription'] === 'annulé' ? 'danger' : 'primary') ?>">
                                                <?= ucfirst($inscrit['statut_inscription']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card-footer">
            <a href="../evenements.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Retour à la liste
            </a>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cet événement ? Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <a href="delete.php?id=<?= $event['id_evenement'] ?>" class="btn btn-danger">Supprimer</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
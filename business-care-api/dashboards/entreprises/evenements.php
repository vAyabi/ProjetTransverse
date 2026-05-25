<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les prestataires disponibles
$stmt = $conn->prepare("SELECT * FROM prestataires WHERE statut_validation = 'validé'");
$stmt->execute();
$prestataires = $stmt->fetchAll();

// Récupérer les événements de l'entreprise
$stmt = $conn->prepare("SELECT e.*, p.nom as prestataire_nom, 
                              COUNT(DISTINCT ie.id_salarie) as nombre_inscrits
                       FROM evenements e 
                       LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
                       LEFT JOIN inscriptions_evenements ie ON e.id_evenement = ie.id_evenement
                       WHERE e.id_entreprise = ?
                       GROUP BY e.id_evenement 
                       ORDER BY e.date_debut DESC");
$stmt->execute([$_SESSION['user_id']]);
$evenements = $stmt->fetchAll();

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestion des événements</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEventModal">
            <i class="fas fa-plus"></i> Nouvel événement
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
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Prestataire</th>
                            <th>Date</th>
                            <th>Inscrits</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($evenements): ?>
                            <?php foreach($evenements as $event): ?>
                                <tr>
                                    <td><?= htmlspecialchars($event['titre']) ?></td>
                                    <td><?= ucfirst($event['type_evenement']) ?></td>
                                    <td><?= htmlspecialchars($event['prestataire_nom']) ?></td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                        <br>
                                        <small class="text-muted">
                                            jusqu'au <?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?= $event['nombre_inscrits'] ?> 
                                        <?php if($event['capacite_max']): ?>
                                            / <?= $event['capacite_max'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $event['statut'] === 'programmé' ? 'primary' :
                                            ($event['statut'] === 'en_cours' ? 'success' : 
                                            ($event['statut'] === 'terminé' ? 'secondary' : 'danger'))
                                        ?>">
                                            <?= ucfirst($event['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="evenements/view.php?id=<?= $event['id_evenement'] ?>" 
                                               class="btn btn-sm btn-info" title="Voir les détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="evenements/edit.php?id=<?= $event['id_evenement'] ?>" 
                                               class="btn btn-sm btn-primary" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteEvent(<?= $event['id_evenement'] ?>)"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Aucun événement</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="addEventModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nouvel événement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="evenements/add.php" method="POST">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Titre</label>
                            <input type="text" class="form-control" name="titre" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" name="type_evenement" required>
                                <option value="webinar">Webinar</option>
                                <option value="conference">Conférence</option>
                                <option value="atelier">Atelier</option>
                                <option value="medical">Médical</option>
                                <option value="sport">Sport</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Date de début</label>
                            <input type="datetime-local" class="form-control" name="date_debut" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date de fin</label>
                            <input type="datetime-local" class="form-control" name="date_fin" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label class="form-label">Prestataire</label>
                            <select class="form-select" name="id_prestataire" required>
                                <option value="">Sélectionner un prestataire</option>
                                <?php foreach($prestataires as $prestataire): ?>
                                    <option value="<?= $prestataire['id_prestataire'] ?>">
                                        <?= htmlspecialchars($prestataire['nom']) ?> 
                                        (<?= ucfirst($prestataire['specialite']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capacité maximale</label>
                            <input type="number" class="form-control" name="capacite_max">
                            <small class="text-muted">Laisser vide si illimité</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Créer l'événement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function deleteEvent(id) {
    if(confirm('Êtes-vous sûr de vouloir supprimer cet événement ?')) {
        window.location.href = 'evenements/delete.php?id=' + id;
    }
}
</script>

<?php include '../includes/footer_dashboard.php'; ?>
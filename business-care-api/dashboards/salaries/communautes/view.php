<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer l'ID du salarié connecté
$id_salarie = $_SESSION['user_id'] ?? 0;

// Vérifier id communauté
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id_communaute = $_GET['id'];

// Récupérer les infos de la communauté
$stmt = $conn->prepare("
    SELECT c.*, s.nom as nom_createur,
           (SELECT COUNT(*) FROM communautes_membres WHERE id_communaute = c.id_communaute) as nombre_membres,
           (SELECT role FROM communautes_membres WHERE id_communaute = c.id_communaute AND id_salarie = ?) as role_utilisateur
    FROM communautes c
    JOIN salaries s ON c.id_createur = s.id_salarie
    WHERE c.id_communaute = ?
");
$stmt->execute([$id_salarie, $id_communaute]);
$communaute = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$communaute) {
    header('Location: index.php');
    exit;
}

// Vérifier si l'utilisateur est membre
$est_membre = !empty($communaute['role_utilisateur']);
$est_admin = $communaute['role_utilisateur'] == 'admin';

// Récupérer les membres
$stmt = $conn->prepare("
    SELECT cm.*, s.nom as nom_salarie
    FROM communautes_membres cm
    JOIN salaries s ON cm.id_salarie = s.id_salarie
    WHERE cm.id_communaute = ?
    ORDER BY cm.role DESC, cm.date_adhesion ASC
");
$stmt->execute([$id_communaute]);
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les publications
$stmt = $conn->prepare("
    SELECT cp.*, s.nom as nom_auteur
    FROM communautes_publications cp
    JOIN salaries s ON cp.id_auteur = s.id_salarie
    WHERE cp.id_communaute = ? AND cp.modere = 1
    ORDER BY cp.date_publication DESC
");
$stmt->execute([$id_communaute]);
$publications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les événements
$stmt = $conn->prepare("
    SELECT ce.*, s.nom as nom_createur
    FROM communautes_evenements ce
    JOIN salaries s ON ce.id_createur = s.id_salarie
    WHERE ce.id_communaute = ?
    ORDER BY ce.date_debut ASC
");
$stmt->execute([$id_communaute]);
$evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclure le header
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';
?>

<div class="container mt-4">
    <!-- En-tête communauté -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h1><?= htmlspecialchars($communaute['nom']) ?></h1>
                <div>
                    <?php if (!$est_membre): ?>
                    <a href="join.php?id=<?= $id_communaute ?>" class="btn btn-light">Rejoindre</a>
                    <?php elseif (!$est_admin): ?>
                    <a href="leave.php?id=<?= $id_communaute ?>" class="btn btn-light" onclick="return confirm('Voulez-vous vraiment quitter cette communauté ?')">Quitter</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <p>
                        <span class="badge bg-success"><?= ucfirst(htmlspecialchars($communaute['categorie'])) ?></span>
                        <span class="ms-2"><?= $communaute['nombre_membres'] ?> membre(s)</span>
                        <span class="ms-2">Créée par <?= htmlspecialchars($communaute['nom_createur']) ?></span>
                        <span class="ms-2">le <?= date('d/m/Y', strtotime($communaute['date_creation'])) ?></span>
                    </p>
                    <p><?= nl2br(htmlspecialchars($communaute['description'])) ?></p>
                </div>
                <?php if ($communaute['image']): ?>
                <div class="col-md-4">
                    <img src="/business_care/<?= htmlspecialchars($communaute['image']) ?>" class="img-fluid rounded" alt="<?= htmlspecialchars($communaute['nom']) ?>">
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Colonne de gauche: Publications -->
        <div class="col-md-8">
            <?php if ($est_membre): ?>
            <!-- Formulaire de publication -->
            <div class="card mb-4">
                <div class="card-body">
                    <h4>Partagez avec la communauté</h4>
                    <form action="post.php" method="post">
                        <input type="hidden" name="id_communaute" value="<?= $id_communaute ?>">
                        <div class="mb-3">
                            <textarea class="form-control" name="contenu" rows="3" placeholder="Écrivez votre message ici..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Publier</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Publications -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h3>Publications</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($publications)): ?>
                    <p class="text-center">Aucune publication pour le moment.</p>
                    <?php else: ?>
                    <?php foreach ($publications as $publication): ?>
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <h5><?= htmlspecialchars($publication['nom_auteur']) ?></h5>
                            <small class="text-muted">
                                <?= date('d/m/Y H:i', strtotime($publication['date_publication'])) ?>
                                <?php if (isset($publication['date_modification']) && $publication['date_modification']): ?>
                                (modifié le <?= date('d/m/Y H:i', strtotime($publication['date_modification'])) ?>)
                                <?php endif; ?>
                            </small>
                        </div>
                        <p><?= nl2br(htmlspecialchars($publication['contenu'])) ?></p>
                        
                        <div class="d-flex justify-content-end mt-2">
                            <?php if ($publication['id_auteur'] == $id_salarie): ?>
                            <a href="edit_post.php?id=<?= $publication['id_publication'] ?>&communaute=<?= $id_communaute ?>" 
                               class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($publication['id_auteur'] == $id_salarie || $est_admin): ?>
                            <a href="delete_post.php?id=<?= $publication['id_publication'] ?>&communaute=<?= $id_communaute ?>" 
                               class="btn btn-sm btn-outline-danger me-2" 
                               onclick="return confirm('Supprimer cette publication ?')">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($publication['id_auteur'] != $id_salarie): ?>
                            <a href="report_post.php?id=<?= $publication['id_publication'] ?>&communaute=<?= $id_communaute ?>" 
                               class="btn btn-sm btn-outline-warning">
                                <i class="fas fa-flag"></i> Signaler
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Colonne de droite: Membres et événements -->
        <div class="col-md-4">
            <!-- Membres -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h3>Membres (<?= count($membres) ?>)</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php foreach ($membres as $membre): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <?= htmlspecialchars($membre['nom_salarie']) ?>
                            <?php if ($membre['role'] == 'admin'): ?>
                            <span class="badge bg-success">Admin</span>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Événements -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h3>Événements à venir</h3>
                    <?php if ($est_membre): ?>
                    <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#creerEvenement">
                        <i class="fas fa-plus"></i> Créer
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($evenements)): ?>
                    <p class="text-center">Aucun événement prévu.</p>
                    <?php else: ?>
                    <?php foreach ($evenements as $evt): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5><?= htmlspecialchars($evt['titre']) ?></h5>
                            <p class="small text-muted">
                                <i class="far fa-calendar"></i> <?= date('d/m/Y H:i', strtotime($evt['date_debut'])) ?>
                            </p>
                            <?php if ($evt['lieu']): ?>
                            <p class="small text-muted">
                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($evt['lieu']) ?>
                            </p>
                            <?php endif; ?>
                            <p class="small"><?= mb_substr(htmlspecialchars($evt['description']), 0, 100) ?>...</p>
                            <a href="events/view.php?id=<?= $evt['id_evenement_communaute'] ?>" class="btn btn-sm btn-outline-success">Détails</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Créer Événement -->
<?php if ($est_membre): ?>
<div class="modal fade" id="creerEvenement" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="events/create.php" method="post">
                <input type="hidden" name="id_communaute" value="<?= $id_communaute ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Créer un événement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="titre" class="form-label">Titre</label>
                        <input type="text" class="form-control" id="titre" name="titre" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="lieu" class="form-label">Lieu (optionnel)</label>
                        <input type="text" class="form-control" id="lieu" name="lieu">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="datetime-local" class="form-control" id="date_debut" name="date_debut" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="datetime-local" class="form-control" id="date_fin" name="date_fin" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Message de notification -->
<?php if (isset($_SESSION['success']) || isset($_SESSION['error']) || isset($_SESSION['info'])): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <?php if (isset($_SESSION['success'])): ?>
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">Succès</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= $_SESSION['success'] ?>
        </div>
    </div>
    <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-danger text-white">
            <strong class="me-auto">Erreur</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= $_SESSION['error'] ?>
        </div>
    </div>
    <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['info'])): ?>
    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-info text-white">
            <strong class="me-auto">Information</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">
            <?= $_SESSION['info'] ?>
        </div>
    </div>
    <?php unset($_SESSION['info']); ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des toasts
    var toastElList = [].slice.call(document.querySelectorAll('.toast'));
    var toastList = toastElList.map(function(toastEl) {
        return new bootstrap.Toast(toastEl, {
            autohide: true,
            delay: 5000
        });
    });
});
</script>
<?php endif; ?>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
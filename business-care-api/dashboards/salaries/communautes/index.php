<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer l'ID du salarié connecté
$id_salarie = $_SESSION['user_id'] ?? 0;

// Récupérer les communautés
$stmt = $conn->prepare("
    SELECT c.*, 
           (SELECT COUNT(*) FROM communautes_membres WHERE id_communaute = c.id_communaute) as nombre_membres,
           (SELECT COUNT(*) FROM communautes_membres WHERE id_communaute = c.id_communaute AND id_salarie = ?) as est_membre
    FROM communautes c
    ORDER BY c.date_creation DESC
");
$stmt->execute([$id_salarie]);
$communautes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer mes communautés
$stmt = $conn->prepare("
    SELECT c.* 
    FROM communautes c
    JOIN communautes_membres cm ON c.id_communaute = cm.id_communaute
    WHERE cm.id_salarie = ?
");
$stmt->execute([$id_salarie]);
$mes_communautes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Inclure le header
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';
?>

<div class="container mt-4">
    <h1>Communautés</h1>
    
    <div class="d-flex justify-content-between mb-4">
        <div>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#creerCommunaute">
                <i class="fas fa-plus"></i> Créer une communauté
            </button>
        </div>
        
        <div class="btn-group">
            <button class="btn btn-outline-success active" id="filtre-toutes">Toutes</button>
            <button class="btn btn-outline-success" id="filtre-sport">Sport</button>
            <button class="btn btn-outline-success" id="filtre-culture">Culture</button>
            <button class="btn btn-outline-success" id="filtre-bien_etre">Bien-être</button>
            <button class="btn btn-outline-success" id="filtre-loisirs">Loisirs</button>
            <button class="btn btn-outline-success" id="filtre-autre">Autre</button>
        </div>
    </div>
    
    <?php if (!empty($mes_communautes)): ?>
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h3>Mes communautés</h3>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($mes_communautes as $comm): ?>
                <div class="col-md-4 mb-3">
                    <div class="service-card h-100">
                        <?php if ($comm['image']): ?>
                        <img src="/business_care/<?= htmlspecialchars($comm['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($comm['nom']) ?>">
                        <?php else: ?>
                        <div class="text-center py-3 bg-light">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <span class="badge bg-success"><?= ucfirst(htmlspecialchars($comm['categorie'])) ?></span>
                            <h5 class="mt-2"><?= htmlspecialchars($comm['nom']) ?></h5>
                            <p class="text-muted"><?= mb_substr(htmlspecialchars($comm['description']), 0, 100) ?>...</p>
                            <a href="view.php?id=<?= $comm['id_communaute'] ?>" class="btn btn-outline-success">Accéder</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header bg-success text-white">
            <h3>Toutes les communautés</h3>
        </div>
        <div class="card-body">
            <div class="row" id="liste-communautes">
                <?php if (empty($communautes)): ?>
                <div class="col-12 text-center">
                    <p>Aucune communauté n'est disponible pour le moment.</p>
                    <p>Créez la première communauté !</p>
                </div>
                <?php else: ?>
                <?php foreach ($communautes as $comm): ?>
                <div class="col-md-4 mb-3 communaute" data-categorie="<?= htmlspecialchars($comm['categorie']) ?>">
                    <div class="service-card h-100">
                        <?php if ($comm['image']): ?>
                        <img src="/business_care/<?= htmlspecialchars($comm['image']) ?>" class="card-img-top" alt="<?= htmlspecialchars($comm['nom']) ?>">
                        <?php else: ?>
                        <div class="text-center py-3 bg-light">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <span class="badge bg-success"><?= ucfirst(htmlspecialchars($comm['categorie'])) ?></span>
                            <h5 class="mt-2"><?= htmlspecialchars($comm['nom']) ?></h5>
                            <p class="text-muted"><?= mb_substr(htmlspecialchars($comm['description']), 0, 100) ?>...</p>
                            <p><small><?= $comm['nombre_membres'] ?> membre(s)</small></p>
                            
                            <div class="d-flex justify-content-between">
                                <a href="view.php?id=<?= $comm['id_communaute'] ?>" class="btn btn-outline-success">Voir</a>
                                <?php if ($comm['est_membre']): ?>
                                <span class="badge bg-success align-self-center p-2">Membre</span>
                                <?php else: ?>
                                <a href="join.php?id=<?= $comm['id_communaute'] ?>" class="btn btn-success">Rejoindre</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour créer une communauté -->
<div class="modal fade" id="creerCommunaute" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="create.php" method="post" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Créer une nouvelle communauté</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nom" class="form-label">Nom de la communauté</label>
                        <input type="text" class="form-control" id="nom" name="nom" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="categorie" class="form-label">Catégorie</label>
                        <select class="form-select" id="categorie" name="categorie" required>
                            <option value="sport">Sport</option>
                            <option value="culture">Culture</option>
                            <option value="bien_etre">Bien-être</option>
                            <option value="loisirs">Loisirs</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="image" class="form-label">Image (optionnelle)</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage des communautés
    const filtres = document.querySelectorAll('.btn-group .btn');
    const communautes = document.querySelectorAll('.communaute');
    
    filtres.forEach(filtre => {
        filtre.addEventListener('click', function() {
            // Retirer classe active de tous les boutons
            filtres.forEach(f => f.classList.remove('active'));
            // Ajouter classe active au bouton cliqué
            this.classList.add('active');
            
            const categorie = this.id.replace('filtre-', '');
            
            communautes.forEach(comm => {
                if (categorie === 'toutes' || comm.dataset.categorie === categorie) {
                    comm.style.display = 'block';
                } else {
                    comm.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
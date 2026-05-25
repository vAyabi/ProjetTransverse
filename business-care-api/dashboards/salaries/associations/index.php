<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';
require_once '../../../config/stripe.php';

$db = new Database();
$conn = $db->getConnection();

// Récupérer les associations
$stmt = $conn->prepare("SELECT * FROM associations ORDER BY nom");
$stmt->execute();
$associations = $stmt->fetchAll();

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <h2>Associations partenaires</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="row mt-4">
        <?php foreach($associations as $association): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($association['nom']) ?></h5>
                        <span class="badge bg-info mb-2"><?= ucfirst($association['type']) ?></span>
                        <p class="card-text"><?= htmlspecialchars($association['description']) ?></p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?= $association['id_association'] ?>">
                            Participer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal pour choisir le type de participation -->
            <div class="modal fade" id="modal<?= $association['id_association'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Participer - <?= htmlspecialchars($association['nom']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <h6>Choisissez votre type de participation:</h6>
                            <div class="d-grid gap-2">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDon<?= $association['id_association'] ?>" data-bs-dismiss="modal">
                                    Don financier
                                </button>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalMateriel<?= $association['id_association'] ?>" data-bs-dismiss="modal">
                                    Don matériel
                                </button>
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBenevolat<?= $association['id_association'] ?>" data-bs-dismiss="modal">
                                    Bénévolat
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal pour Don Financier -->
            <div class="modal fade" id="modalDon<?= $association['id_association'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Don financier - <?= htmlspecialchars($association['nom']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="create_stripe_session.php" method="GET">
                            <div class="modal-body">
                                <input type="hidden" name="id_association" value="<?= $association['id_association'] ?>">
                                <div class="mb-3">
                                    <label class="form-label">Montant du don (€)</label>
                                    <input type="number" name="amount" min="0.50" step="0.01" class="form-control" required>
                                    <small class="text-muted">Montant minimum : 0.50€</small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Payer avec Stripe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal pour Don Matériel -->
            <div class="modal fade" id="modalMateriel<?= $association['id_association'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Don matériel - <?= htmlspecialchars($association['nom']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="add_participation.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_association" value="<?= $association['id_association'] ?>">
                                <input type="hidden" name="type_participation" value="don_materiel">
                                <div class="mb-3">
                                    <label class="form-label">Description du don</label>
                                    <textarea name="description" class="form-control" rows="3" required placeholder="Décrivez ce que vous souhaitez donner (ex: matériel informatique, vêtements, etc.)"></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        Votre don sera enregistré et l'association vous contactera pour organiser la remise du matériel.
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal pour Bénévolat -->
            <div class="modal fade" id="modalBenevolat<?= $association['id_association'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Bénévolat - <?= htmlspecialchars($association['nom']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form action="add_participation.php" method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="id_association" value="<?= $association['id_association'] ?>">
                                <input type="hidden" name="type_participation" value="benevolat">
                                <div class="mb-3">
                                    <label class="form-label">Disponibilités</label>
                                    <textarea name="disponibilites" class="form-control" rows="2" required placeholder="Vos disponibilités (jours et horaires)"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Compétences ou domaines d'intérêt</label>
                                    <textarea name="competences" class="form-control" rows="2" required placeholder="Ex: informatique, animation, accueil, logistique..."></textarea>
                                </div>
                                <div class="alert alert-info">
                                    <small>
                                        <i class="bi bi-info-circle"></i> 
                                        Votre participation sera enregistrée et l'association vous contactera pour discuter des possibilités de bénévolat.
                                    </small>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Confirmer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Historique des participations -->
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Mes participations</h5>
        </div>
        <div class="card-body">
            <?php 
            // Récupérer les participations avec leurs détails
            $stmt = $conn->prepare("
                SELECT pa.id_participation, pa.date_participation, pa.type_participation, 
                       a.nom as association_nom, pd.description, pd.disponibilites, pd.competences
                FROM participations_associations pa 
                LEFT JOIN participation_details pd ON pa.id_participation = pd.id_participation
                JOIN associations a ON pa.id_association = a.id_association 
                WHERE pa.id_salarie = ? 
                ORDER BY pa.date_participation DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $participations = $stmt->fetchAll();
            ?>
            
            <?php if($participations): ?>
                <div class="accordion" id="participationsAccordion">
                    <?php foreach($participations as $index => $participation): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="heading<?= $index ?>">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $index ?>" aria-expanded="false" 
                                        aria-controls="collapse<?= $index ?>">
                                    <div class="d-flex justify-content-between w-100 me-3">
                                        <span><?= date('d/m/Y', strtotime($participation['date_participation'])) ?></span>
                                        <span><?= htmlspecialchars($participation['association_nom']) ?></span>
                                        <span class="badge bg-info">
                                            <?= str_replace('_', ' ', ucfirst($participation['type_participation'])) ?>
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse<?= $index ?>" class="accordion-collapse collapse" 
                                 aria-labelledby="heading<?= $index ?>" data-bs-parent="#participationsAccordion">
                                <div class="accordion-body">
                                    <?php if($participation['type_participation'] === 'don_materiel' && $participation['description']): ?>
                                        <p><strong>Description du don:</strong> <?= nl2br(htmlspecialchars($participation['description'])) ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if($participation['type_participation'] === 'benevolat'): ?>
                                        <?php if($participation['disponibilites']): ?>
                                            <p><strong>Disponibilités:</strong> <?= nl2br(htmlspecialchars($participation['disponibilites'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <?php if($participation['competences']): ?>
                                            <p><strong>Compétences:</strong> <?= nl2br(htmlspecialchars($participation['competences'])) ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <form action="cancel_participation.php" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir annuler cette participation?');">
                                                <input type="hidden" name="id_participation" value="<?= $participation['id_participation'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    Annuler ma participation
                                                </button>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Aucune participation pour le moment</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
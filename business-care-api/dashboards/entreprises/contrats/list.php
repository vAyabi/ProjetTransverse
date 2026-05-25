<?php
// dashboards/entreprises/contrats/list.php
session_start();
require_once '../../../config/Database.php';

if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

$db = new Database();
$conn = $db->getConnection();

// Récupérer tous les contrats
$stmt = $conn->prepare("SELECT * FROM contrats WHERE id_entreprise = ? ORDER BY date_debut DESC");
$stmt->execute([$_SESSION['user_id']]);
$contrats = $stmt->fetchAll();

// Vérifier s'il y a un contrat actif
$stmt = $conn->prepare("SELECT * FROM contrats WHERE id_entreprise = ? AND statut = 'actif'");
$stmt->execute([$_SESSION['user_id']]);
$contrat_actif = $stmt->fetch();

// Récupérer la formule actuelle
$stmt = $conn->prepare("SELECT type_formule FROM entreprises WHERE id_entreprise = ?");
$stmt->execute([$_SESSION['user_id']]);
$entreprise = $stmt->fetch();

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Historique des contrats</h2>
        <?php if(!$contrat_actif): ?>
        <a href="../demande_devis.php" class="btn btn-success">
            <i class="fas fa-file-invoice me-2"></i>Demander un devis
        </a>
        <?php endif; ?>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if($contrat_actif): ?>
    <div class="alert alert-info">
        <h5>Contrat actif</h5>
        <p>Vous avez actuellement un contrat <?= ucfirst($entreprise['type_formule']) ?> actif jusqu'au <?= date('d/m/Y', strtotime($contrat_actif['date_fin'])) ?>.</p>
    </div>
    <?php else: ?>
    <div class="alert alert-warning">
        <p>Vous n'avez pas de contrat actif. <a href="../demande_devis.php">Demandez un devis</a> pour souscrire à une formule Business Care.</p>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Liste des contrats</h5>
        </div>
        <div class="card-body">
            <?php if(empty($contrats)): ?>
                <p class="text-center">Aucun contrat trouvé.</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Date début</th>
                            <th>Date fin</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($contrats as $contrat): ?>
                            <tr>
                                <td>#<?= $contrat['id_contrat'] ?></td>
                                <td><?= date('d/m/Y', strtotime($contrat['date_debut'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($contrat['date_fin'])) ?></td>
                                <td><?= ucfirst($contrat['type_paiement']) ?></td>
                                <td><?= number_format($contrat['montant_total'], 2, ',', ' ') ?> €</td>
                                <td>
                                    <span class="badge bg-<?= $contrat['statut'] === 'actif' ? 'success' : 
                                        ($contrat['statut'] === 'terminé' ? 'secondary' : 'danger') ?>">
                                        <?= ucfirst($contrat['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?= $contrat['id_contrat'] ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Détails
                                    </a>
                                    <a href="download.php?id=<?= $contrat['id_contrat'] ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-download"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
<?php
// dashboards/entreprises/contrats/view.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

if(!isset($_GET['id'])) {
    header('Location: list.php');
    exit();
}

// Récupérer le contrat et ses factures
$stmt = $conn->prepare("
    SELECT c.*
    FROM contrats c
    WHERE c.id_contrat = ? AND c.id_entreprise = ?
");
$stmt->execute([$_GET['id'], $_SESSION['user_id']]);
$contrat = $stmt->fetch();

if(!$contrat) {
    header('Location: list.php');
    exit();
}

// Récupérer les factures de ce contrat
$stmt = $conn->prepare("
    SELECT * FROM factures 
    WHERE id_contrat = ? 
    ORDER BY date_echeance DESC
");
$stmt->execute([$_GET['id']]);
$factures = $stmt->fetchAll();

// Récupérer la formule de l'entreprise
$stmt = $conn->prepare("SELECT type_formule FROM entreprises WHERE id_entreprise = ?");
$stmt->execute([$_SESSION['user_id']]);
$entreprise = $stmt->fetch();

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Détails du contrat #<?= str_pad($contrat['id_contrat'], 6, '0', STR_PAD_LEFT) ?></h2>
        <div>
            <a href="download.php?id=<?= $contrat['id_contrat'] ?>" class="btn btn-primary">
                <i class="fas fa-download me-2"></i>Télécharger PDF
            </a>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Retour
            </a>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Informations générales</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Date de début:</strong> <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?></p>
                    <p><strong>Date de fin:</strong> <?= date('d/m/Y', strtotime($contrat['date_fin'])) ?></p>
                    <p><strong>Formule:</strong> 
                        <span class="badge bg-info"><?= ucfirst($entreprise['type_formule']) ?></span>
                    </p>
                </div>
                <div class="col-md-6">
                    <p><strong>Type de paiement:</strong> <?= ucfirst($contrat['type_paiement']) ?></p>
                    <p><strong>Montant:</strong> <?= number_format($contrat['montant_total'], 2, ',', ' ') ?> €
                        <?= $contrat['type_paiement'] === 'mensuel' ? '/mois' : '/an' ?>
                    </p>
                    <p><strong>Statut:</strong> 
                        <span class="badge bg-<?= $contrat['statut'] === 'actif' ? 'success' : 
                            ($contrat['statut'] === 'résilié' ? 'danger' : 'warning') ?>">
                            <?= ucfirst($contrat['statut']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Factures associées</h5>
        </div>
        <div class="card-body">
            <?php if(empty($factures)): ?>
                <p class="text-center">Aucune facture pour ce contrat.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date d'échéance</th>
                                <th>Montant</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($factures as $facture): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($facture['date_echeance'])) ?></td>
                                    <td><?= number_format($facture['montant_total'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <span class="badge bg-<?= $facture['statut'] === 'payée' ? 'success' : 
                                            ($facture['statut'] === 'retard' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($facture['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="download.php?id=<?= $facture['id_facture'] ?>" 
                                           class="btn btn-sm btn-primary">
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
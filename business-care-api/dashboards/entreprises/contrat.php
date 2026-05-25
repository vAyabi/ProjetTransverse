<?php

session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';

$db = new Database();
$conn = $db->getConnection();

// récupère le contrat actif
$stmt = $conn->prepare("SELECT * FROM contrats WHERE id_entreprise = ? AND statut = 'actif'");
$stmt->execute([$_SESSION['user_id']]);
$contrat = $stmt->fetch();

// récupère l'historique des factures
$stmt = $conn->prepare("
    SELECT * FROM factures 
    WHERE id_entreprise = ? 
    ORDER BY date_echeance DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$factures_recentes = $stmt->fetchAll();

// récupère le type de formule de l'entreprise
$stmt = $conn->prepare("SELECT type_formule FROM entreprises WHERE id_entreprise = ?");
$stmt->execute([$_SESSION['user_id']]);
$entreprise = $stmt->fetch();

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Mon Contrat</h2>
        <div>
            <?php if($contrat): ?>
                <a href="contrats/list.php" class="btn btn-outline-primary">
                    <i class="fas fa-list me-2"></i>Historique
                </a>
            <?php else: ?>
                <a href="demande_devis.php" class="btn btn-primary">
                    <i class="fas fa-file-invoice me-2"></i>Demander un devis
                </a>
            <?php endif; ?>
        </div>
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

    <?php if($contrat): ?>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Contrat en cours</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>N° Contrat:</strong> #<?= str_pad($contrat['id_contrat'], 6, '0', STR_PAD_LEFT) ?></p>
                                <p><strong>Date de début:</strong> <?= date('d/m/Y', strtotime($contrat['date_debut'])) ?></p>
                                <p><strong>Date de fin:</strong> <?= date('d/m/Y', strtotime($contrat['date_fin'])) ?></p>
                                <p><strong>Formule:</strong> 
                                    <span class="badge bg-info"><?= ucfirst($entreprise['type_formule']) ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Montant:</strong> <?= number_format($contrat['montant_total'], 2, ',', ' ') ?> €
                                    <?= $contrat['type_paiement'] === 'mensuel' ? '/mois' : '/an' ?>
                                </p>
                                <p><strong>Type de paiement:</strong> <?= ucfirst($contrat['type_paiement']) ?></p>
                                <p><strong>Statut:</strong> 
                                    <span class="badge bg-success"><?= ucfirst($contrat['statut']) ?></span>
                                </p>
                                <p><strong>Prochaine échéance:</strong> 
                                    <?php
                                    if($contrat['type_paiement'] === 'mensuel') {
                                        echo date('d/m/Y', strtotime('+1 month'));
                                    } else {
                                        echo date('d/m/Y', strtotime($contrat['date_fin']));
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Services inclus</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        $limites = [
                            'starter' => [
                                'activites' => 2,
                                'rdv' => 1,
                                'chatbot' => 6,
                                'conseils' => false
                            ],
                            'basic' => [
                                'activites' => 3,
                                'rdv' => 2,
                                'chatbot' => 20,
                                'conseils' => true
                            ],
                            'premium' => [
                                'activites' => 4,
                                'rdv' => 3,
                                'chatbot' => 'illimité',
                                'conseils' => true
                            ]
                        ];
                        
                        $formule_details = $limites[$entreprise['type_formule']] ?? null;
                        ?>
                        
                        <?php if($formule_details): ?>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success me-2"></i><?= $formule_details['activites'] ?> activités</li>
                            <li><i class="fas fa-check text-success me-2"></i><?= $formule_details['rdv'] ?> RDV médicaux</li>
                            <li><i class="fas fa-check text-success me-2"></i>Chatbot: <?= $formule_details['chatbot'] ?> questions</li>
                            <li><i class="fas fa-check text-success me-2"></i>Fiches pratiques</li>
                            <?php if($formule_details['conseils']): ?>
                                <li><i class="fas fa-check text-success me-2"></i>Conseils hebdomadaires</li>
                            <?php endif; ?>
                        </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Factures récentes</h5>
            </div>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($factures_recentes)): ?>
                            <tr>
                                <td colspan="4" class="text-center">Aucune facture trouvée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($factures_recentes as $facture): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($facture['date_echeance'])) ?></td>
                                    <td><?= number_format($facture['montant_total'], 2, ',', ' ') ?> €</td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $facture['statut'] === 'payée' ? 'success' : 
                                            ($facture['statut'] === 'retard' ? 'danger' : 'warning') 
                                        ?>">
                                            <?= ucfirst($facture['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="factures/download2.php?id=<?= $facture['id_facture'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-download"></i> PDF
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if(!empty($factures_recentes)): ?>
            <div class="card-footer text-center">
                <a href="contrats/list.php" class="btn btn-sm btn-outline-primary">
                    Voir toutes les factures
                </a>
            </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-center">
            <img src="/assets/img/contract-illustration.svg" alt="Contrat" style="max-width: 300px; margin-bottom: 2rem;">
            <h4>Aucun contrat actif</h4>
            <p class="text-muted">Pour souscrire à Business Care, commencez par demander un devis.</p>
            <a href="demande_devis.php" class="btn btn-primary btn-lg">
                <i class="fas fa-file-invoice me-2"></i>Demander un devis
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
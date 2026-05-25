<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

include '../includes/header_dashboard.php';
?>

<div id="dashboard-container" class="container mt-4" data-entreprise-id="<?= $_SESSION['user_id'] ?>">
    <div id="alerts-container"></div>
    
    <h2>Tableau de bord - <span id="entreprise-nom">Chargement...</span></h2>

    
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Formule</h6>
                    <p class="h4" id="entreprise-formule">Chargement...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Salariés inscrits</h6>
                    <p class="h4" id="count-salaries">Chargement...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Événements à venir</h6>
                    <p class="h4" id="count-evenements">Chargement...</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Statut contrat</h6>
                    <p class="h4" id="statut-contrat">
                        <span class="badge bg-secondary">Chargement...</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Actions rapides</h5>
        </div>
        <div class="card-body">
            <div class="d-flex gap-2">
                <a href="salaries.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> Gérer les salariés
                </a>
                <a href="evenements.php" class="btn btn-success">
                    <i class="fas fa-calendar"></i> Gérer les événements
                </a>
                <a href="contrat.php" class="btn btn-info">
                    <i class="fas fa-file-contract"></i> Voir mon contrat
                </a>
                <a href="profil.php" class="btn btn-secondary">
                    <i class="fas fa-user-cog"></i> Paramètres
                </a>
            </div>
        </div>
    </div>

    
    <div class="card mt-4 d-none" id="facturation-info">
        <div class="card-header">
            <h5 class="mb-0">Informations de facturation</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Type de paiement:</strong> <span id="payment-type">-</span></p>
                    <p><strong>Montant:</strong> <span id="payment-amount">-</span></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Date de début:</strong> <span id="payment-start">-</span></p>
                    <p><strong>Date de fin:</strong> <span id="payment-end">-</span></p>
                </div>
            </div>
        </div>
    </div>
</div>


<script src="/business-care-api/asset/js/entreprise-dashboard.js"></script>

<?php include '../includes/footer_dashboard.php'; ?>
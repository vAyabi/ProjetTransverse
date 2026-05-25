<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['logged_in'])) {
    header('Location: /business-care-api/login.php');
    exit();
}

$dashboard_titles = [
    'entreprises' => 'Espace Entreprise',
    'prestataires' => 'Espace Prestataire',
    'salaries' => 'Espace Salarié'
];

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
$db = new Database();
$conn = $db->getConnection();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $dashboard_titles[$_SESSION['user_type']] ?? 'Dashboard' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/business-care-api/asset/css/style.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
   
<nav class="navbar navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="/business-care-api/dashboards/entreprises/index.php">
            <img src="/business-care-api/asset/images/logo.png" alt="Logo" width="30" height="30" class="d-inline-block align-text-top">
            <?= $dashboard_titles[$_SESSION['user_type']] ?? 'Dashboard' ?>
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if($_SESSION['user_type'] === 'entreprises'): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/entreprises/salaries.php">
                            <i class="fas fa-users"></i> Gestion des salariés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/entreprises/contrat.php">
                            <i class="fas fa-file-contract"></i> Mon contrat
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/entreprises/demande_devis.php">
                            <i class="fas fa-file-contract"></i> Demande de devis
                        </a>
                    </li>
                </ul>
            <?php elseif($_SESSION['user_type'] === 'salaries'): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/index.php">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/evenements.php">
                            <i class="fas fa-calendar"></i> Événements
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/rdv_medicaux/index.php">
                            <i class="fas fa-user-md"></i> RDV Médicaux
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/conseils/index.php">
                            <i class="fas fa-lightbulb"></i> Conseils
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/associations/index.php">
                            <i class="fas fa-hands-helping"></i> Associations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/communautes/">
                            <i class="fas fa-users"></i> Communautés
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/planning/">
                            <i class="fas fa-calendar-alt"></i> Planning
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/salaries/signalement.php">
                            <i class="fas fa-flag"></i> Signalement
                        </a>
                    </li>
                </ul>
            <?php elseif($_SESSION['user_type'] === 'prestataires'): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/prestataires/index.php">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/prestataires/planning.php">
                            <i class="fas fa-calendar"></i> Planning
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/prestataires/rdv_medicaux.php">
                            <i class="fas fa-user-md"></i> RDV Médicaux
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/prestataires/services.php">
                            <i class="fas fa-cog"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/dashboards/prestataires/profil.php">
                            <i class="fas fa-user"></i> Profil
                        </a>
                    </li>
                </ul>
            <?php endif; ?>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_nom']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if($_SESSION['user_type'] === 'salaries'): ?>
                            <li>
                                <a class="dropdown-item" href="/business-care-api/dashboards/<?= $_SESSION['user_type'] ?>/profil/">
                                    <i class="fas fa-user-cog"></i> Mon profil
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li>
                            <a class="dropdown-item" href="/business-care-api/logout.php">
                                <i class="fas fa-sign-out-alt"></i> Déconnexion
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
       
    </div>
</nav>

<div class="container-fluid py-4">
<?php
// Dans includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Business Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/business-care-api/asset/css/admin.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="/business-care-api/admin/dashboard.php">
            <img src="/business-care-api/asset/images/logo.png" alt="Logo Business Care" class="logo">
                <span>Business Care Admin</span>
            </a>
            
            <!-- bouton hamburger -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarAdmin" aria-controls="navbarAdmin" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- menu de navigation -->
            <div class="collapse navbar-collapse" id="navbarAdmin">
                <?php if(isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="/business-care-api/admin/entreprise/index.php">
                                <i class="fas fa-building me-2"></i>Entreprises
                                
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/business-care-api/admin/salaries/index.php">
                                <i class="fas fa-users me-2"></i>Salariés
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/business-care-api/admin/prestataires/index.php">
                                <i class="fas fa-handshake me-2"></i>Prestataires
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/business-care-api/admin/evenements/index.php">
                                <i class="fas fa-calendar me-2"></i>Événements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/business-care-api/admin/contrats/index.php">
                                <i class="fas fa-file-contract me-2"></i>Contrats/Devis
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/admin/administrateurs/index.php">
                                <i class="fas fa-file-contract me-2"></i>Administrateurs
                            </a>
                        </li>
                        <li class="nav-item">
                        <a class="nav-link" href="/business-care-api/admin/signalements/index.php">
                                <i class="fas fa-file-contract me-2"></i>Signalement
                            </a>
                        </li>
                    </ul>

                    <!-- menu utilisateur -->
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-2"></i>
                                <?= htmlspecialchars($_SESSION['admin_nom'] ?? 'Admin') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="admin_logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>
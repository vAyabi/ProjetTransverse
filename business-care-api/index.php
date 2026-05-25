<?php 
// index.php avec système multilingue
require_once 'config/languages.php';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Care - <?= __('hero_title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./asset/css/style.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../business-care-api/index.php">
                <img src="./asset/images/logo.png" alt="Logo Business Care" class="logo">
                <span>Business Care</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><?= __('home') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="services.php"><?= __('services') ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php"><?= __('about') ?></a>
                    </li>
                </ul>
                <div class="nav-buttons">
                    <a href="login.php" class="btn btn-outline-light me-2"><?= __('login') ?></a>
                    <a href="register.php" class="btn btn-light"><?= __('register') ?></a>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-text">
                    <h1><?= __('hero_title') ?></h1>
                    <p><?= __('hero_subtitle') ?></p>
                    <div class="hero-buttons">
                        <a href="services.php" class="btn btn-primary"><?= __('discover_services') ?></a>
                        <a href="contact.php" class="btn btn-outline-secondary"><?= __('contact_us') ?></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="services">
        <div class="container">
            <br><h2 class="text-center"><?= __('our_services') ?></h2>
            <p class="text-center mb-5"><?= __('services_subtitle') ?></p>
            
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-heartbeat"></i>
                        <h3><?= __('workplace_health') ?></h3>
                        <p><?= __('workplace_health_desc') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-brain"></i>
                        <h3><?= __('mental_wellbeing') ?></h3>
                        <p><?= __('mental_wellbeing_desc') ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="service-card">
                        <i class="fas fa-users"></i>
                        <h3><?= __('team_cohesion') ?></h3>
                        <p><?= __('team_cohesion_desc') ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <div class="footer-info">
                        <h5>Business Care</h5>
                        <p><?= __('footer_description') ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <h5><?= __('navigation') ?></h5>
                    <ul class="footer-links">
                        <li><a href="/"><?= __('home') ?></a></li>
                        <li><a href="/actualites.php"><?= __('news') ?></a></li>
                        <li><a href="/services.php"><?= __('services') ?></a></li>
                        <li><a href="/about.php"><?= __('about') ?></a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5><?= __('contact') ?></h5>
                    <ul class="footer-contact">
                        <li><i class="fas fa-map-marker-alt"></i> <?= __('address') ?></li>
                        <li><i class="fas fa-phone"></i> <?= __('phone') ?></li>
                        <li><i class="fas fa-envelope"></i> <?= __('email') ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?= date('Y') ?> Business Care. <?= __('all_rights_reserved') ?></p>
            </div>
        </div>

        <div class="language-selector">
            <select id="languageSelect" onchange="changeLanguage(this.value)">
                <?php foreach($languages as $code => $lang): ?>
                    <option value="<?= $code ?>" <?= $current_lang === $code ? 'selected' : '' ?>>
                        <?= $lang['flag'] ?> <?= $lang['name'] ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Fonction pour changer la langue
    function changeLanguage(lang) {
        fetch('/business-care-api/ajax/change_language.php?lang=' + lang)
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    // Recharger la page pour appliquer la nouvelle langue
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
            });
    }

    // Initialiser la langue depuis le cookie si disponible
    document.addEventListener('DOMContentLoaded', function() {
        const cookieLang = getCookie('user_lang');
        if(cookieLang && cookieLang !== '<?= $current_lang ?>') {
            document.getElementById('languageSelect').value = cookieLang;
        }
    });

    // Fonction pour lire un cookie
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) return parts.pop().split(';').shift();
    }
    </script>

    <style>
    .language-selector {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 1000;
    }

    .language-selector select {
        padding: 8px 15px;
        border-radius: 25px;
        border: 2px solid #007bff;
        background-color: white;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .language-selector select:hover {
        background-color: #007bff;
        color: white;
    }

    .language-selector select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.25);
    }
    </style>
</body>
</html>
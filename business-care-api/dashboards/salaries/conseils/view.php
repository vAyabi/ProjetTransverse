
<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

if(!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

require_once '../../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer le conseil
$stmt = $conn->prepare("SELECT * FROM conseils WHERE id_conseil = ?");
$stmt->execute([$_GET['id']]);
$conseil = $stmt->fetch();

if(!$conseil) {
    header('Location: index.php');
    exit();
}

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Conseils</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($conseil['titre']) ?></li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-body">
            <span class="badge bg-info mb-3">
                <?= ucfirst(str_replace('_', ' ', $conseil['categorie'])) ?>
            </span>
            <h1 class="card-title h3 mb-4"><?= htmlspecialchars($conseil['titre']) ?></h1>
            
            <div class="card-text mb-4">
                <?= nl2br(htmlspecialchars($conseil['contenu'])) ?>
            </div>

            <div class="text-muted">
                Publié le <?= date('d/m/Y à H:i', strtotime($conseil['date_creation'])) ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
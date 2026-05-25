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

// Vérifier les paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_publication']) && isset($_POST['raison'])) {
    $id_publication = $_POST['id_publication'];
    $id_communaute = $_POST['id_communaute'];
    $raison = trim($_POST['raison']);
    $details = trim($_POST['details'] ?? '');

    if (empty($raison)) {
        $_SESSION['error'] = "Veuillez indiquer une raison pour ce signalement.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }

    try {
        // Vérifier si la publication existe
        $stmt = $conn->prepare("SELECT id_communaute FROM communautes_publications WHERE id_publication = ?");
        $stmt->execute([$id_publication]);
        if (!$stmt->fetch()) {
            $_SESSION['error'] = "Publication introuvable.";
            header('Location: view.php?id=' . $id_communaute);
            exit;
        }

        // Vérifier si déjà signalée par cet utilisateur
        $stmt = $conn->prepare("
            SELECT id FROM communautes_signalements 
            WHERE id_publication = ? AND id_salarie = ?
        ");
        $stmt->execute([$id_publication, $id_salarie]);
        if ($stmt->fetch()) {
            $_SESSION['info'] = "Vous avez déjà signalé cette publication.";
            header('Location: view.php?id=' . $id_communaute);
            exit;
        }

        // Ajouter le signalement
        $stmt = $conn->prepare("
            INSERT INTO communautes_signalements (id_publication, id_salarie, raison, details, date_signalement)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$id_publication, $id_salarie, $raison, $details]);

        $_SESSION['success'] = "Signalement enregistré. Merci pour votre contribution.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors du signalement : " . $e->getMessage();
    }

    header('Location: view.php?id=' . $id_communaute);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['communaute'])) {
    $id_publication = $_GET['id'];
    $id_communaute = $_GET['communaute'];

    // Vérifier si la publication existe
    $stmt = $conn->prepare("
        SELECT cp.*, c.nom as nom_communaute, s.nom as nom_auteur
        FROM communautes_publications cp
        JOIN communautes c ON cp.id_communaute = c.id_communaute
        JOIN salaries s ON cp.id_auteur = s.id_salarie
        WHERE cp.id_publication = ?
    ");
    $stmt->execute([$id_publication]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$publication) {
        $_SESSION['error'] = "Publication introuvable.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }

    // Afficher le formulaire de signalement
    require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Communautés</a></li>
            <li class="breadcrumb-item"><a href="view.php?id=<?= $id_communaute ?>"><?= htmlspecialchars($publication['nom_communaute']) ?></a></li>
            <li class="breadcrumb-item active">Signaler un contenu</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header bg-warning text-white">
            <h2>Signaler un contenu</h2>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <p><strong>Message signalé :</strong></p>
                <div class="border p-3 mb-3 bg-light">
                    <div class="mb-2"><strong><?= htmlspecialchars($publication['nom_auteur']) ?></strong></div>
                    <p><?= nl2br(htmlspecialchars($publication['contenu'])) ?></p>
                </div>
            </div>

            <form action="report_post.php" method="post">
                <input type="hidden" name="id_publication" value="<?= $id_publication ?>">
                <input type="hidden" name="id_communaute" value="<?= $id_communaute ?>">
                <div class="mb-3">
                    <label for="raison" class="form-label">Raison du signalement</label>
                    <select class="form-select mb-2" name="raison" id="raison" required>
                        <option value="">Choisir une raison...</option>
                        <option value="Contenu inapproprié">Contenu inapproprié</option>
                        <option value="Harcèlement">Harcèlement</option>
                        <option value="Spam">Spam</option>
                        <option value="Information erronée">Information erronée</option>
                        <option value="Autre">Autre raison</option>
                    </select>
                    <textarea class="form-control" id="details" name="details" rows="3" placeholder="Détails supplémentaires (optionnel)"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-warning">Envoyer le signalement</button>
                    <a href="view.php?id=<?= $id_communaute ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php';
} else {
    header('Location: index.php');
    exit;
}
?>
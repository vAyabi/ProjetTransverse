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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_publication']) && isset($_POST['contenu'])) {
    $id_publication = $_POST['id_publication'];
    $id_communaute = $_POST['id_communaute'];
    $contenu = trim($_POST['contenu']);

    if (empty($contenu)) {
        $_SESSION['error'] = "Le message ne peut pas être vide.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }

    try {
        // Vérifier si l'utilisateur est l'auteur
        $stmt = $conn->prepare("
            SELECT id_communaute, id_auteur 
            FROM communautes_publications 
            WHERE id_publication = ?
        ");
        $stmt->execute([$id_publication]);
        $publication = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$publication || $publication['id_auteur'] != $id_salarie) {
            $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier ce message.";
            header('Location: view.php?id=' . $id_communaute);
            exit;
        }

        // Appliquer la modération automatique
        $mots_interdits = ['insulte', 'grossier', 'mot_inapproprie'];
        foreach ($mots_interdits as $mot) {
            $contenu = str_ireplace($mot, '***', $contenu);
        }

        // Mettre à jour le message
        $stmt = $conn->prepare("
            UPDATE communautes_publications 
            SET contenu = ?, date_modification = NOW() 
            WHERE id_publication = ?
        ");
        $stmt->execute([$contenu, $id_publication]);

        $_SESSION['success'] = "Message modifié avec succès.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur lors de la modification : " . $e->getMessage();
    }

    header('Location: view.php?id=' . $id_communaute);
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id']) && isset($_GET['communaute'])) {
    $id_publication = $_GET['id'];
    $id_communaute = $_GET['communaute'];

    // Vérifier si l'utilisateur est l'auteur
    $stmt = $conn->prepare("
        SELECT cp.*, c.nom as nom_communaute 
        FROM communautes_publications cp
        JOIN communautes c ON cp.id_communaute = c.id_communaute
        WHERE cp.id_publication = ?
    ");
    $stmt->execute([$id_publication]);
    $publication = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$publication || $publication['id_auteur'] != $id_salarie) {
        $_SESSION['error'] = "Vous n'êtes pas autorisé à modifier ce message.";
        header('Location: view.php?id=' . $id_communaute);
        exit;
    }

    // Afficher le formulaire de modification
    require_once $_SERVER['DOCUMENT_ROOT'] . '/business_care/dashboards/includes/header_dashboard.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Communautés</a></li>
            <li class="breadcrumb-item"><a href="view.php?id=<?= $id_communaute ?>"><?= htmlspecialchars($publication['nom_communaute']) ?></a></li>
            <li class="breadcrumb-item active">Modifier un message</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h2>Modifier votre message</h2>
        </div>
        <div class="card-body">
            <form action="edit_post.php" method="post">
                <input type="hidden" name="id_publication" value="<?= $id_publication ?>">
                <input type="hidden" name="id_communaute" value="<?= $id_communaute ?>">
                <div class="mb-3">
                    <label for="contenu" class="form-label">Contenu du message</label>
                    <textarea class="form-control" id="contenu" name="contenu" rows="5" required><?= htmlspecialchars($publication['contenu']) ?></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success">Enregistrer les modifications</button>
                    <a href="view.php?id=<?= $id_communaute ?>" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/business_care/dashboards/includes/footer_dashboard.php';
} else {
    header('Location: index.php');
    exit;
}
?>
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

// Vérifier ID événement
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../index.php');
    exit;
}

$id_evenement = $_GET['id'];

// Récupérer infos événement
$stmt = $conn->prepare("
    SELECT ce.*, c.nom as nom_communaute, c.id_communaute, s.nom as nom_createur,
           (SELECT statut FROM communautes_participants WHERE id_evenement_communaute = ce.id_evenement_communaute AND id_salarie = ?) as statut_participation
    FROM communautes_evenements ce
    JOIN communautes c ON ce.id_communaute = c.id_communaute
    JOIN salaries s ON ce.id_createur = s.id_salarie
    WHERE ce.id_evenement_communaute = ?
");
$stmt->execute([$id_salarie, $id_evenement]);
$evenement = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evenement) {
    header('Location: ../index.php');
    exit;
}

// Récupérer les participants
$stmt = $conn->prepare("
    SELECT cp.*, s.nom as nom_salarie
    FROM communautes_participants cp
    JOIN salaries s ON cp.id_salarie = s.id_salarie
    WHERE cp.id_evenement_communaute = ?
    ORDER BY cp.statut, cp.date_reponse
");
$stmt->execute([$id_evenement]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si membre
$stmt = $conn->prepare("
    SELECT id_membre FROM communautes_membres 
    WHERE id_communaute = ? AND id_salarie = ?
");
$stmt->execute([$evenement['id_communaute'], $id_salarie]);
$est_membre = $stmt->fetch() ? true : false;

require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';
?>

<div class="container mt-4">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="../index.php">Communautés</a></li>
            <li class="breadcrumb-item"><a href="../view.php?id=<?= $evenement['id_communaute'] ?>"><?= htmlspecialchars($evenement['nom_communaute']) ?></a></li>
            <li class="breadcrumb-item active">Événement</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-header bg-success text-white">
            <h1><?= htmlspecialchars($evenement['titre']) ?></h1>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?= nl2br(htmlspecialchars($evenement['description'])) ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Informations</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-user"></i> Organisé par: <?= htmlspecialchars($evenement['nom_createur']) ?>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-calendar-alt"></i> Date de début: <?= date('d/m/Y H:i', strtotime($evenement['date_debut'])) ?>
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-calendar-check"></i> Date de fin: <?= date('d/m/Y H:i', strtotime($evenement['date_fin'])) ?>
                            </li>
                            <?php if ($evenement['lieu']): ?>
                            <li class="list-group-item">
                                <i class="fas fa-map-marker-alt"></i> Lieu: <?= htmlspecialchars($evenement['lieu']) ?>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <?php if ($est_membre): ?>
                    <div class="mb-4">
                        <h5>Votre participation</h5>
                        <?php if (!$evenement['statut_participation']): ?>
                        <div class="d-flex gap-2">
                            <a href="join.php?id=<?= $id_evenement ?>&statut=confirme" class="btn btn-success">Je participe</a>
                            <a href="join.php?id=<?= $id_evenement ?>&statut=peut_etre" class="btn btn-warning">Peut-être</a>
                            <a href="join.php?id=<?= $id_evenement ?>&statut=refuse" class="btn btn-danger">Je ne participe pas</a>
                        </div>
                        <?php elseif ($evenement['statut_participation'] == 'confirme'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> Vous participez à cet événement.
                            <div class="mt-2">
                                <a href="join.php?id=<?= $id_evenement ?>&statut=annuler" class="btn btn-sm btn-outline-danger">Annuler ma participation</a>
                            </div>
                        </div>
                        <?php elseif ($evenement['statut_participation'] == 'peut_etre'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-question-circle"></i> Vous avez indiqué que vous participerez peut-être.
                            <div class="mt-2">
                                <div class="d-flex gap-2">
                                    <a href="join.php?id=<?= $id_evenement ?>&statut=confirme" class="btn btn-sm btn-success">Je participe</a>
                                    <a href="join.php?id=<?= $id_evenement ?>&statut=annuler" class="btn btn-sm btn-outline-danger">Annuler</a>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-times-circle"></i> Vous avez décliné cet événement.
                            <div class="mt-2">
                                <div class="d-flex gap-2">
                                    <a href="join.php?id=<?= $id_evenement ?>&statut=confirme" class="btn btn-sm btn-success">Je participe finalement</a>
                                    <a href="join.php?id=<?= $id_evenement ?>&statut=peut_etre" class="btn btn-sm btn-warning">Peut-être</a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Vous devez être membre de la communauté pour participer.
                        <a href="../join.php?id=<?= $evenement['id_communaute'] ?>" class="btn btn-sm btn-success ms-2">Rejoindre la communauté</a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Participants</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            $confirmes = $peut_etre = $refuses = 0;
                            foreach ($participants as $participant) {
                                if ($participant['statut'] == 'confirme') $confirmes++;
                                elseif ($participant['statut'] == 'peut_etre') $peut_etre++;
                                else $refuses++;
                            }
                            ?>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-check text-success"></i> Confirmés</span>
                                    <span class="badge bg-success rounded-pill"><?= $confirmes ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-question text-warning"></i> Peut-être</span>
                                    <span class="badge bg-warning rounded-pill"><?= $peut_etre ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-times text-danger"></i> Refusés</span>
                                    <span class="badge bg-danger rounded-pill"><?= $refuses ?></span>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <?php if (!empty($participants)): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Liste des participants</h5>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($participants as $participant): ?>
                                <?php if ($participant['statut'] == 'confirme'): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($participant['nom_salarie']) ?>
                                    <span class="badge bg-success">Confirmé</span>
                                </li>
                                <?php elseif ($participant['statut'] == 'peut_etre'): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?= htmlspecialchars($participant['nom_salarie']) ?>
                                    <span class="badge bg-warning">Peut-être</span>
                                </li>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <a href="../view.php?id=<?= $evenement['id_communaute'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Retour à la communauté
            </a>
        </div>
    </div>
</div>

<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>    
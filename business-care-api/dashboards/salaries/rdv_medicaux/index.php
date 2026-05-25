<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "RDV Médicaux";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';

// Récupérer l'ID du salarié
$id_salarie = $_SESSION['user_id'];

// Récupérer le type de formule de l'entreprise
$sql_entreprise = "SELECT type_formule FROM entreprises WHERE id_entreprise = (SELECT id_entreprise FROM salaries WHERE id_salarie = ?)";
$stmt_entreprise = $conn->prepare($sql_entreprise);
$stmt_entreprise->execute([$id_salarie]);
$entreprise = $stmt_entreprise->fetch(PDO::FETCH_ASSOC);

// Déterminer le quota selon la formule
$quota_mensuel = 1; // Par défaut (Starter)
if ($entreprise['type_formule'] == 'basic') {
    $quota_mensuel = 2;
} elseif ($entreprise['type_formule'] == 'premium') {
    $quota_mensuel = 3;
}

// Vérifier le quota restant pour le mois en cours
$mois_actuel = date('n');
$annee_actuelle = date('Y');

$sql_quota = "SELECT quota_disponible FROM quota_rdv_medicaux WHERE id_salarie = ? AND mois = ? AND annee = ?";
$stmt_quota = $conn->prepare($sql_quota);
$stmt_quota->execute([$id_salarie, $mois_actuel, $annee_actuelle]);
$quota = $stmt_quota->fetch(PDO::FETCH_ASSOC);

if (!$quota) {
    // Créer un nouveau quota si c'est le premier du mois
    $sql_insert_quota = "INSERT INTO quota_rdv_medicaux (id_salarie, mois, annee, quota_disponible, quota_total) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert_quota = $conn->prepare($sql_insert_quota);
    $stmt_insert_quota->execute([$id_salarie, $mois_actuel, $annee_actuelle, $quota_mensuel, $quota_mensuel]);
    $quota_restant = $quota_mensuel;
} else {
    $quota_restant = $quota['quota_disponible'];
}

// Récupérer les RDV médicaux à venir
$sql_rdv_a_venir = "SELECT r.*, p.nom as prestataire_nom FROM rendez_vous_medicaux r, prestataires p 
                    WHERE r.id_prestataire = p.id_prestataire 
                    AND r.id_salarie = ? 
                    AND r.date_heure > NOW() 
                    AND r.statut = 'programmé' 
                    ORDER BY r.date_heure ASC";
$stmt_rdv_a_venir = $conn->prepare($sql_rdv_a_venir);
$stmt_rdv_a_venir->execute([$id_salarie]);
$rdvs_a_venir = $stmt_rdv_a_venir->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les RDV médicaux passés (limités aux 5 derniers)
$sql_rdv_passes = "SELECT r.*, p.nom as prestataire_nom FROM rendez_vous_medicaux r, prestataires p 
                   WHERE r.id_prestataire = p.id_prestataire 
                   AND r.id_salarie = ? 
                   AND (r.date_heure < NOW() OR r.statut != 'programmé') 
                   ORDER BY r.date_heure DESC 
                   LIMIT 5";
$stmt_rdv_passes = $conn->prepare($sql_rdv_passes);
$stmt_rdv_passes->execute([$id_salarie]);
$rdvs_passes = $stmt_rdv_passes->fetchAll(PDO::FETCH_ASSOC);

// Vérifier si des prestataires ont des disponibilités
$sql_dispo_count = "SELECT COUNT(*) FROM disponibilites_prestataires";
$stmt_dispo_count = $conn->prepare($sql_dispo_count);
$stmt_dispo_count->execute();
$has_disponibilites = ($stmt_dispo_count->fetchColumn() > 0);

// Traitement des messages d'alertes (succès ou erreur)
$success_message = isset($_GET['success']) && $_GET['success'] == 1 ? "Votre rendez-vous a été réservé avec succès." : "";
$cancel_message = isset($_GET['cancel']) && $_GET['cancel'] == 1 ? "Votre rendez-vous a été annulé avec succès." : "";

// Messages d'erreur
if(isset($_SESSION['error'])) {
    $error_message = $_SESSION['error'];
    unset($_SESSION['error']);
}
?>

<div class="container">
    <h1>RDV Médicaux</h1>
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Votre quota mensuel</h5>
                    <div class="progress mb-3">
                        <?php 
                        $pourcentage = ($quota_restant / $quota_mensuel) * 100;
                        ?>
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pourcentage ?>%;" 
                            aria-valuenow="<?= $quota_restant ?>" aria-valuemin="0" aria-valuemax="<?= $quota_mensuel ?>">
                            <?= $quota_restant ?> / <?= $quota_mensuel ?>
                        </div>
                    </div>
                    <p>Vous avez encore <strong><?= $quota_restant ?></strong> RDV médical(aux) gratuit(s) disponible(s) ce mois-ci.</p>
                    <p>Tarif des RDV supplémentaires : <strong><?= $entreprise['type_formule'] == 'premium' ? '50' : '75' ?>€</strong> par RDV</p>
                    <a href="/business-care-api/dashboards/salaries/rdv_medicaux/reserver.php" class="btn btn-primary">
                        <i class="fas fa-calendar-plus"></i> Réserver un RDV
                    </a>
                    
                    <?php if (!$has_disponibilites): ?>
                        <div class="alert alert-warning mt-3 mb-0">
                            <i class="fas fa-exclamation-triangle"></i> Certains médecins n'ont pas encore configuré leurs disponibilités. Les dates et heures seront limitées.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($cancel_message)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <?= $cancel_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Vos prochains rendez-vous</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($rdvs_a_venir)): ?>
                        <p class="text-muted">Vous n'avez aucun rendez-vous médical à venir.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Prestataire</th>
                                        <th>Type</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rdvs_a_venir as $rdv): ?>
                                        <tr>
                                            <td><?= (new DateTime($rdv['date_heure']))->format('d/m/Y H:i') ?></td>
                                            <td><?= htmlspecialchars($rdv['prestataire_nom']) ?></td>
                                            <td>
                                                <?php if ($rdv['type'] == 'presentiel'): ?>
                                                    <span class="badge bg-primary">Présentiel</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Visio</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($rdv['notes'] ?? '') ?></td>
                                            <td>
                                                <?php if ($rdv['type'] == 'visioconference' && !empty($rdv['jitsi_room_name'])): ?>
                                                    <a href="/business-care-api/dashboards/salaries/rdv_medicaux/visio.php?room=<?= urlencode($rdv['jitsi_room_name']) ?>" 
                                                       class="btn btn-sm btn-success me-1" target="_blank">
                                                        <i class="fas fa-video"></i> Rejoindre
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <a href="/business-care-api/dashboards/salaries/rdv_medicaux/reserver.php?edit=<?= $rdv['id_rdv'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="/business-care-api/dashboards/salaries/rdv_medicaux/reserver.php?cancel=<?= $rdv['id_rdv'] ?>" class="btn btn-sm btn-outline-danger" 
                                                onclick="return confirm('Êtes-vous sûr de vouloir annuler ce rendez-vous ?');">
                                                    <i class="fas fa-times"></i>
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
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Historique de vos rendez-vous</h5>
                    <a href="/business-care-api/dashboards/salaries/rdv_medicaux/historique.php" class="btn btn-sm btn-outline-secondary">Voir tout l'historique</a>
                </div>
                <div class="card-body">
                    <?php if (empty($rdvs_passes)): ?>
                        <p class="text-muted">Vous n'avez aucun historique de rendez-vous médical.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Prestataire</th>
                                        <th>Type</th>
                                        <th>Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rdvs_passes as $rdv): ?>
                                        <tr>
                                            <td><?= (new DateTime($rdv['date_heure']))->format('d/m/Y H:i') ?></td>
                                            <td><?= htmlspecialchars($rdv['prestataire_nom']) ?></td>
                                            <td>
                                                <?php if ($rdv['type'] == 'presentiel'): ?>
                                                    <span class="badge bg-primary">Présentiel</span>
                                                <?php else: ?>
                                                    <span class="badge bg-info">Visio</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($rdv['statut'] == 'terminé'): ?>
                                                    <span class="badge bg-success">Terminé</span>
                                                <?php elseif ($rdv['statut'] == 'annulé'): ?>
                                                    <span class="badge bg-danger">Annulé</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Passé</span>
                                                <?php endif; ?>
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
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Historique des RDV médicaux";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';

$id_salarie = $_SESSION['user_id'];

// Paramètres de pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$start = ($page - 1) * $limit;

// Récupérer le nombre total de RDV
$sql_count = "SELECT COUNT(*) as total FROM rendez_vous_medicaux WHERE id_salarie = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute([$id_salarie]);
$total_rdvs = $stmt_count->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_rdvs / $limit);

// Requête simplifiée sans OFFSET/LIMIT en paramètres
$sql_rdvs = "SELECT * FROM rendez_vous_medicaux WHERE id_salarie = ? ORDER BY date_heure DESC LIMIT $start, $limit";
$stmt_rdvs = $conn->prepare($sql_rdvs);
$stmt_rdvs->execute([$id_salarie]);
$rdvs = $stmt_rdvs->fetchAll(PDO::FETCH_ASSOC);

// Pour récupérer les noms des prestataires séparément
$prestataires = [];
if (!empty($rdvs)) {
    $prestataire_ids = array_column($rdvs, 'id_prestataire');
    $placeholders = implode(',', array_fill(0, count($prestataire_ids), '?'));
    
    $sql_prestataires = "SELECT id_prestataire, nom FROM prestataires WHERE id_prestataire IN ($placeholders)";
    $stmt_prestataires = $conn->prepare($sql_prestataires);
    $stmt_prestataires->execute($prestataire_ids);
    
    // Créer un tableau associatif des prestataires par ID
    foreach ($stmt_prestataires->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $prestataires[$p['id_prestataire']] = $p['nom'];
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/business-care-api/dashboards/salaries/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="/business-care-api/dashboards/salaries/rdv_medicaux/index.php">RDV Médicaux</a></li>
                    <li class="breadcrumb-item active">Historique</li>
                </ol>
            </nav>
        </div>
    </div>

    <h1>Historique de vos rendez-vous médicaux</h1>

    <div class="card">
        <div class="card-body">
            <?php if (empty($rdvs)): ?>
                <p class="text-muted">Vous n'avez aucun historique de rendez-vous médical.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date et heure</th>
                                <th>Praticien</th>
                                <th>Type</th>
                                <th>Notes</th>
                                <th>Statut</th>
                                <th>Hors quota</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rdvs as $rdv): ?>
                                <tr>
                                    <td><?= (new DateTime($rdv['date_heure']))->format('d/m/Y H:i') ?></td>
                                    <td><?= htmlspecialchars($prestataires[$rdv['id_prestataire']] ?? 'Inconnu') ?></td>
                                    <td>
                                        <?php if ($rdv['type'] == 'presentiel'): ?>
                                            <span class="badge bg-primary">Présentiel</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Visioconférence</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($rdv['notes'] ?? 'Aucune note') ?></td>
                                    <td>
                                        <?php if ($rdv['statut'] == 'programmé'): ?>
                                            <span class="badge bg-warning">Programmé</span>
                                        <?php elseif ($rdv['statut'] == 'terminé'): ?>
                                            <span class="badge bg-success">Terminé</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Annulé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($rdv['hors_quota']): ?>
                                            <span class="badge bg-secondary">Payant</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Inclus</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>" aria-label="Précédent">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>" aria-label="Suivant">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
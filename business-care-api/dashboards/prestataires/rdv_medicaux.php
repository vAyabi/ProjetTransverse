<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les RDV médicaux du prestataire
$stmt = $conn->prepare("SELECT rm.*, s.nom as nom_salarie, e.nom as nom_entreprise
                       FROM rendez_vous_medicaux rm 
                       JOIN salaries s ON rm.id_salarie = s.id_salarie
                       JOIN entreprises e ON s.id_entreprise = e.id_entreprise
                       WHERE rm.id_prestataire = ? 
                       ORDER BY rm.date_heure");
$stmt->execute([$_SESSION['user_id']]);
$rdv_medicaux = $stmt->fetchAll();

// Organiser les RDV par date
$rdv_par_date = [];
if($rdv_medicaux) {
    foreach($rdv_medicaux as $rdv) {
        $date = date('Y-m-d', strtotime($rdv['date_heure']));
        if(!isset($rdv_par_date[$date])) {
            $rdv_par_date[$date] = [];
        }
        $rdv_par_date[$date][] = $rdv;
    }
}

include '../includes/header_dashboard.php';
?>

<div class="container mt-4">
    <h2>Mes Rendez-vous Médicaux</h2>
    
    <div class="row mt-4">
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total RDV à venir</h6>
                    <p class="h3"><?= count(array_filter($rdv_medicaux, function($rdv) {
                        return $rdv['statut'] === 'programmé' && strtotime($rdv['date_heure']) > time();
                    })) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">RDV aujourd'hui</h6>
                    <p class="h3"><?= isset($rdv_par_date[date('Y-m-d')]) ? count($rdv_par_date[date('Y-m-d')]) : 0 ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">RDV terminés</h6>
                    <p class="h3"><?= count(array_filter($rdv_medicaux, function($rdv) {
                        return $rdv['statut'] === 'terminé';
                    })) ?></p>
                </div>
            </div>
        </div>
    </div>
    
    
    <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mes prochains rendez-vous</h5>
            <a href="disponibilites.php" class="btn btn-primary btn-sm">
                <i class="fas fa-calendar-alt"></i> Gérer mes disponibilités
            </a>
        </div>
        <div class="card-body">
            <?php if(count($rdv_medicaux) > 0): ?>
                <?php 
                    // Récupérer uniquement les RDV à venir
                    $rdv_a_venir = array_filter($rdv_medicaux, function($rdv) {
                        return $rdv['statut'] === 'programmé' && strtotime($rdv['date_heure']) > time();
                    });
                    
                    // Trier par date
                    usort($rdv_a_venir, function($a, $b) {
                        return strtotime($a['date_heure']) - strtotime($b['date_heure']);
                    });
                ?>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Horaire</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Entreprise</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rdv_a_venir as $rdv): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($rdv['date_heure'])) ?></td>
                                    <td><?= date('H:i', strtotime($rdv['date_heure'])) ?></td>
                                    <td><?= htmlspecialchars($rdv['nom_salarie']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $rdv['type'] === 'presentiel' ? 'success' : 'primary' ?>">
                                            <?= ucfirst($rdv['type']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($rdv['nom_entreprise']) ?></td>
                                    <td>
                                        <?php if($rdv['type'] === 'visioconference' && !empty($rdv['jitsi_room_name'])): ?>
                                            <a href="/business-care-api/dashboards/salaries/rdv_medicaux/visio.php?room=<?= urlencode($rdv['jitsi_room_name']) ?>" 
                                               class="btn btn-primary btn-sm me-2" target="_blank">
                                                <i class="fas fa-video"></i> Rejoindre
                                            </a>
                                        <?php endif; ?>
                                        
                                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#terminerRdvModal<?= $rdv['id_rdv'] ?>">
                                            <i class="fas fa-check"></i> Terminer
                                        </button>
                                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#annulerRdvModal<?= $rdv['id_rdv'] ?>">
                                            <i class="fas fa-times"></i> Annuler
                                        </button>
                                    </td>
                                </tr>
                                
                                
                                <div class="modal fade" id="terminerRdvModal<?= $rdv['id_rdv'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Terminer le rendez-vous</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Confirmez-vous que le rendez-vous avec <?= htmlspecialchars($rdv['nom_salarie']) ?> du <?= date('d/m/Y à H:i', strtotime($rdv['date_heure'])) ?> est terminé ?</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <a href="terminer_rdv.php?id=<?= $rdv['id_rdv'] ?>" class="btn btn-success">Confirmer</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                
                                <div class="modal fade" id="annulerRdvModal<?= $rdv['id_rdv'] ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Annuler le rendez-vous</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Êtes-vous sûr de vouloir annuler le rendez-vous avec <?= htmlspecialchars($rdv['nom_salarie']) ?> du <?= date('d/m/Y à H:i', strtotime($rdv['date_heure'])) ?> ?</p>
                                                <div class="form-text text-danger">Cette action ne peut pas être annulée.</div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                <a href="annuler_rdv.php?id=<?= $rdv['id_rdv'] ?>" class="btn btn-danger">Confirmer</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if(count($rdv_a_venir) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucun rendez-vous à venir</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Vous n'avez pas de rendez-vous médicaux pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    
    <div class="card mt-4">
        <div class="card-header">
            <h5 class="mb-0">Historique des rendez-vous</h5>
        </div>
        <div class="card-body">
            <?php 
                $rdv_passes = array_filter($rdv_medicaux, function($rdv) {
                    return $rdv['statut'] === 'terminé' || $rdv['statut'] === 'annulé';
                });
                
                // Trier par date décroissante
                usort($rdv_passes, function($a, $b) {
                    return strtotime($b['date_heure']) - strtotime($a['date_heure']);
                });
            ?>
            
            <?php if(count($rdv_passes) > 0): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Patient</th>
                                <th>Type</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($rdv_passes as $rdv): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($rdv['date_heure'])) ?></td>
                                    <td><?= htmlspecialchars($rdv['nom_salarie']) ?></td>
                                    <td><?= ucfirst($rdv['type']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $rdv['statut'] === 'terminé' ? 'success' : 'danger' ?>">
                                            <?= ucfirst($rdv['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    Vous n'avez pas d'historique de rendez-vous.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
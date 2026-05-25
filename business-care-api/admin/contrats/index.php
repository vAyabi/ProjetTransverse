<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}


function callAPI($endpoint, $method = 'GET', $data = null) {
    $apiUrl = "http://localhost/business-care-api/api/" . $endpoint;
    
    $ch = curl_init($apiUrl);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
   
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } else if ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    }
    
   
    $headers = ['Content-Type: application/json'];
    if (isset($_SESSION['admin_token'])) {
        $headers[] = 'Authorization: Bearer ' . $_SESSION['admin_token'];
    }
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    
    
    $response = curl_exec($ch);
    
   
    if (curl_errno($ch)) {
        $_SESSION['error'] = "Erreur API: " . curl_error($ch);
        curl_close($ch);
        return false;
    }
    
    curl_close($ch);
    return json_decode($response, true);
}

try {
    
    $resultContrats = callAPI("contrat/findAll.php");
    
    if ($resultContrats && isset($resultContrats['data']['contrats'])) {
        $contrats = $resultContrats['data']['contrats']; 
    } else {
        $contrats = [];
    }
    
    
    $resultDevis = callAPI("devis/findAll.php");
    
    if ($resultDevis && isset($resultDevis['data']['devis'])) {
        $devis = $resultDevis['data']['devis']; 
    } else {
        $devis = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $contrats = [];
    $devis = [];
}

function getStatutContratColor($statut) {
    switch($statut) {
        case 'actif': return 'success';
        case 'résilié': return 'danger';
        case 'terminé': return 'secondary';
        default: return 'warning';
    }
}

function getStatutDevisColor($statut) {
    switch($statut) {
        case 'accepté': return 'success';
        case 'refusé': return 'danger';
        case 'expiré': return 'secondary';
        default: return 'warning'; 
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['warning']) && isset($_SESSION['confirm_delete'])): ?>
        <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Confirmation de suppression</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p><?= $_SESSION['warning'] ?></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <a href="delete.php?id=<?= $_SESSION['confirm_delete'] ?>&confirm=yes" class="btn btn-danger">
                            Oui, supprimer tout
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('confirmDeleteModal')).show();
            });
        </script>
        <?php unset($_SESSION['warning'], $_SESSION['confirm_delete']); ?>
    <?php endif; ?>

    
    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="contrats-tab" data-bs-toggle="tab" data-bs-target="#contrats-tab-pane" type="button" role="tab" aria-controls="contrats-tab-pane" aria-selected="true">
                Contrats
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="devis-tab" data-bs-toggle="tab" data-bs-target="#devis-tab-pane" type="button" role="tab" aria-controls="devis-tab-pane" aria-selected="false">
                Devis
            </button>
        </li>
    </ul>

    <div class="tab-content" id="myTabContent">
       
        <div class="tab-pane fade show active" id="contrats-tab-pane" role="tabpanel" aria-labelledby="contrats-tab" tabindex="0">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0">Gestion des Contrats</h1>
                <a href="add.php" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-2"></i> Nouveau contrat
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Entreprise</th>
                                    <th>Date début</th>
                                    <th>Date fin</th>
                                    <th>Montant</th>
                                    <th>Type paiement</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($contrats)): ?>
                                    <?php foreach($contrats as $contrat): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($contrat['entreprise_nom'] ?? $contrat['nom_entreprise'] ?? 'N/A') ?></td>
                                            <td><?= date('d/m/Y', strtotime($contrat['date_debut'] ?? 'now')) ?></td>
                                            <td><?= date('d/m/Y', strtotime($contrat['date_fin'] ?? 'now')) ?></td>
                                            <td><?= number_format($contrat['montant_total'] ?? 0, 2, ',', ' ') ?> €</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= ucfirst($contrat['type_paiement'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= getStatutContratColor($contrat['statut'] ?? '') ?>">
                                                    <?= ucfirst($contrat['statut'] ?? 'N/A') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <?php if(isset($contrat['id_contrat'])): ?>
                                                        <a href="edit.php?id=<?= $contrat['id_contrat'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                onclick="deleteContrat(<?= $contrat['id_contrat'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">ID manquant</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Aucun contrat trouvé
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        
        <div class="tab-pane fade" id="devis-tab-pane" role="tabpanel" aria-labelledby="devis-tab" tabindex="0">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0">Gestion des Devis</h1>
                <a href="add_devis.php" class="btn btn-success d-flex align-items-center">
                    <i class="fas fa-plus me-2"></i> Nouveau devis
                </a>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Entreprise</th>
                                    <th>Montant</th>
                                    <th>Validité (jours)</th>
                                    <th>Date création</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($devis)): ?>
                                    <?php foreach($devis as $d): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($d['entreprise_nom'] ?? 'N/A') ?></td>
                                            <td><?= number_format($d['montant_total'] ?? 0, 2, ',', ' ') ?> €</td>
                                            <td><?= $d['validite_jours'] ?? 30 ?> jours</td>
                                            <td><?= date('d/m/Y', strtotime($d['created_at'] ?? 'now')) ?></td>
                                            <td>
                                                <span class="badge bg-<?= getStatutDevisColor($d['statut'] ?? '') ?>">
                                                    <?= ucfirst($d['statut'] ?? 'en_attente') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <?php if(isset($d['id_devis'])): ?>
                                                        <a href="edit_devis.php?id=<?= $d['id_devis'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                onclick="deleteDevis(<?= $d['id_devis'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">ID manquant</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Aucun devis trouvé
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation de suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="deleteModalMessage">Êtes-vous sûr de vouloir supprimer cet élément ?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Supprimer</button>
            </div>
        </div>
    </div>
</div>

<script>
    
    let deleteModal;
    document.addEventListener('DOMContentLoaded', function() {
        deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    });
    
    
    function deleteContrat(id) {
        document.getElementById('deleteModalMessage').textContent = 'Êtes-vous sûr de vouloir supprimer ce contrat ?';
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            
            fetch('delete.php?id=' + id, {
                method: 'GET'
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload(); 
                } else {
                    throw new Error('Erreur lors de la suppression');
                }
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            })
            .finally(() => {
                deleteModal.hide();
            });
        };
        
        deleteModal.show();
    }
    
    
    function deleteDevis(id) {
        document.getElementById('deleteModalMessage').textContent = 'Êtes-vous sûr de vouloir supprimer ce devis ?';
        
        document.getElementById('confirmDeleteBtn').onclick = function() {
            
            fetch('delete_devis.php?id=' + id, {
                method: 'GET'
            })
            .then(response => {
                if (response.ok) {
                    window.location.reload(); 
                } else {
                    throw new Error('Erreur lors de la suppression');
                }
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            })
            .finally(() => {
                deleteModal.hide();
            });
        };
        
        deleteModal.show();
    }
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
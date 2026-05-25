<?php
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
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
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
    
    $result = callAPI("entreprise/findAll.php");
    if ($result && isset($result['data']) && isset($result['data']['entreprises'])) {
        $entreprises = $result['data']['entreprises'];
    } else {
        // Pour déboguer
        // echo '<pre>'; print_r($result); echo '</pre>'; exit;
        $entreprises = [];
    }
    
    
    $result = callAPI("entreprise/findAllArchived.php");
    if ($result && isset($result['data']) && isset($result['data']['entreprises_archivees'])) {
        $entreprises_archivees = $result['data']['entreprises_archivees'];
    } else {
        $entreprises_archivees = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $entreprises = [];
    $entreprises_archivees = [];
}


function getFormuleColor($formule) {
    switch($formule) {
        case 'premium': return 'warning';
        case 'basic': return 'info';
        default: return 'secondary';
    }
}

function getStatutColor($statut) {
    switch($statut) {
        case 1: return 'success';   
        case 0: return 'warning';   
        case 2: return 'danger';    
        default: return 'secondary';
    }
}

function getStatutLabel($statut) {
    switch($statut) {
        case 1: return 'Actif';
        case 0: return 'En attente';
        case 2: return 'Inactif';
        default: return 'Inconnu';
    }
}



include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Gestion des Entreprises</h1>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i> Nouvelle entreprise
        </a>
    </div>

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

   
    <ul class="nav nav-tabs mb-3" id="enterpriseTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-enterprises" 
                type="button" role="tab" aria-controls="active-enterprises" aria-selected="true">
                Entreprises actives
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-enterprises" 
                type="button" role="tab" aria-controls="archived-enterprises" aria-selected="false">
                Entreprises archivées
            </button>
        </li>
    </ul>

    <div class="tab-content" id="enterpriseTabsContent">
        
        <div class="tab-pane fade show active" id="active-enterprises" role="tabpanel" aria-labelledby="active-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Téléphone</th>
                                    <th>Type Formule</th>
                                    <th>Statut</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($entreprises)): ?>
                                    <?php foreach($entreprises as $entreprise): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($entreprise['nom'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($entreprise['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($entreprise['telephone'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getFormuleColor($entreprise['type_formule'] ?? 'starter') ?>">
                                                    <?= ucfirst($entreprise['type_formule'] ?? 'starter') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $statut = $entreprise['statut'] ?? 0;
                                                $statutColor = getStatutColor($statut);
                                                $statutLabel = getStatutLabel($statut);
                                                ?>
                                                <span class="badge bg-<?= $statutColor ?>">
                                                    <?= $statutLabel ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <?php if(isset($entreprise['id_entreprise'])): ?>
                                                        <?php if($entreprise['statut'] == 0): ?>
                                                            <a href="validate.php?id=<?= $entreprise['id_entreprise'] ?>" 
                                                                class="btn btn-sm btn-success" 
                                                                title="Valider l'entreprise">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                        <a href="edit.php?id=<?= $entreprise['id_entreprise'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#archiveModal<?= $entreprise['id_entreprise'] ?>">
                                                            <i class="fas fa-archive"></i>
                                                        </button>

                                                        
                                                        <div class="modal fade" id="archiveModal<?= $entreprise['id_entreprise'] ?>">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Archivage d'entreprise</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Êtes-vous sûr de vouloir archiver l'entreprise "<?= htmlspecialchars($entreprise['nom'] ?? 'N/A') ?>" et tous ses salariés ?</p>
                                                                        <p class="text-muted small">Cette action archivera l'entreprise et ses salariés au lieu de les supprimer définitivement.</p>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="raison_archivage<?= $entreprise['id_entreprise'] ?>" class="form-label">Raison de l'archivage</label>
                                                                            <textarea class="form-control" id="raison_archivage<?= $entreprise['id_entreprise'] ?>" rows="3" 
                                                                                      placeholder="Précisez la raison de l'archivage..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                        <button type="button" class="btn btn-danger btn-archive" 
                                                                                data-id="<?= $entreprise['id_entreprise'] ?>"
                                                                                data-bs-dismiss="modal">
                                                                            Archiver
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
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
                                                Aucune entreprise trouvée
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
        
        
        <div class="tab-pane fade" id="archived-enterprises" role="tabpanel" aria-labelledby="archived-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Type Formule</th>
                                    <th>Date d'archivage</th>
                                    <th>Raison</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(!empty($entreprises_archivees)): ?>
                                    <?php foreach($entreprises_archivees as $entreprise): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($entreprise['nom'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($entreprise['email'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge bg-<?= getFormuleColor($entreprise['type_formule'] ?? 'starter') ?>">
                                                    <?= ucfirst($entreprise['type_formule'] ?? 'starter') ?>
                                                </span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($entreprise['date_archivage'])) ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="<?= htmlspecialchars($entreprise['raison_archivage'] ?? 'Non spécifié') ?>">
                                                    Voir la raison
                                                </button>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <?php if(isset($entreprise['id_entreprise_original'])): ?>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-info"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#viewEmployeesModal<?= $entreprise['id_entreprise_original'] ?>">
                                                            <i class="fas fa-users"></i> Voir les salariés
                                                        </button>

                                                       
                                                        <div class="modal fade" id="viewEmployeesModal<?= $entreprise['id_entreprise_original'] ?>">
                                                            <div class="modal-dialog modal-lg">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Salariés archivés - <?= htmlspecialchars($entreprise['nom']) ?></h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="employees-container" 
                                                                             data-enterprise-id="<?= $entreprise['id_entreprise_original'] ?>">
                                                                            <div class="text-center">
                                                                                <div class="spinner-border text-primary" role="status">
                                                                                    <span class="visually-hidden">Chargement...</span>
                                                                                </div>
                                                                                <p>Chargement des salariés...</p>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
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
                                                Aucune entreprise archivée trouvée
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    
    document.querySelectorAll('.btn-archive').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const raison = document.getElementById('raison_archivage' + id).value;
            
           
            document.body.style.cursor = 'wait';
            
            
            fetch('http://localhost/business-care-api/api/entreprise/archive.php', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id_entreprise: id,
                    raison_archivage: raison
                })
            })
            .then(response => response.json())
            .then(data => {
                document.body.style.cursor = 'default';
                
                if (data.status) {
                    
                    alert('Entreprise archivée avec succès');
                    location.reload();
                } else {
                    
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                document.body.style.cursor = 'default';
                console.error('Erreur:', error);
                alert('Une erreur s\'est produite lors de l\'archivage.');
            });
        });
    });
    
   
    document.querySelectorAll('[id^="viewEmployeesModal"]').forEach(modal => {
        modal.addEventListener('shown.bs.modal', function () {
            const container = this.querySelector('.employees-container');
            const enterpriseId = container.getAttribute('data-enterprise-id');
            
            
            fetch(`http://localhost/business-care-api/api/entreprise/findArchivedEmployees.php?id=${enterpriseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status && data.data && data.data.salaries) {
                        
                        let html = '';
                        if (data.data.salaries.length > 0) {
                            html = `
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Statut</th>
                                            <th>Date d'archivage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                            `;
                            
                            data.data.salaries.forEach(salarie => {
                                html += `
                                    <tr>
                                        <td>${salarie.nom}</td>
                                        <td>${salarie.email}</td>
                                        <td>${salarie.statut == 1 ? 'Actif' : 'Inactif'}</td>
                                        <td>${new Date(salarie.date_archivage).toLocaleString()}</td>
                                    </tr>
                                `;
                            });
                            
                            html += `
                                    </tbody>
                                </table>
                            `;
                        } else {
                            html = `<div class="alert alert-info">Aucun salarié archivé pour cette entreprise.</div>`;
                        }
                        
                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `<div class="alert alert-danger">Erreur lors du chargement des salariés.</div>`;
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    container.innerHTML = `<div class="alert alert-danger">Une erreur s'est produite lors du chargement des salariés.</div>`;
                });
        });
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
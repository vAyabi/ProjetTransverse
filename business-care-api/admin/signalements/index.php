<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';

if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

$page_title = "Gestion des signalements";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';

$db = new Database();
$conn = $db->getConnection();


$sql = "
    SELECT s.*, 
           sal.nom as nom_salarie, 
           sal.email as email_salarie,
           ent.nom as nom_entreprise,
           sal.id_entreprise
    FROM signalements s
    JOIN salaries sal ON s.id_salarie = sal.id_salarie
    JOIN entreprises ent ON sal.id_entreprise = ent.id_entreprise
    ORDER BY 
        CASE 
            WHEN s.statut = 'nouveau' THEN 1
            WHEN s.statut = 'en_traitement' THEN 2
            ELSE 3
        END,
        CASE 
            WHEN s.urgence = 'élevé' THEN 1
            WHEN s.urgence = 'moyen' THEN 2
            ELSE 3
        END,
        s.date_signalement DESC
";
$stmt = $conn->prepare($sql);
$stmt->execute();
$signalements = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : null;
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Gestion des signalements</h1>
    
    <div id="alert-container">
        <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Liste des signalements</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Urgence</th>
                            <th>Entreprise</th>
                            <th>Salarié</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($signalements as $signalement): ?>
                            <tr class="<?= $signalement['statut'] === 'nouveau' ? 'table-warning' : ($signalement['statut'] === 'en_traitement' ? 'table-info' : 'table-success') ?>">
                                <td><?= $signalement['id_signalement'] ?></td>
                                <td><?= ucfirst(str_replace('_', ' ', $signalement['type'])) ?></td>
                                <td>
                                    <?php 
                                    $urgence_badge = match($signalement['urgence']) {
                                        'élevé' => 'bg-danger',
                                        'moyen' => 'bg-warning',
                                        'faible' => 'bg-info',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $urgence_badge ?>"><?= ucfirst($signalement['urgence']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($signalement['nom_entreprise']) ?></td>
                                <td><?= $signalement['anonyme'] ? '<em>Anonyme</em>' : htmlspecialchars($signalement['nom_salarie']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($signalement['date_signalement'])) ?></td>
                                <td>
                                    <?php 
                                    $status_badge = match($signalement['statut']) {
                                        'nouveau' => 'bg-warning',
                                        'en_traitement' => 'bg-info',
                                        'traité' => 'bg-success',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?= $status_badge ?>"><?= ucfirst(str_replace('_', ' ', $signalement['statut'])) ?></span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewSignalementModal<?= $signalement['id_signalement'] ?>">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal<?= $signalement['id_signalement'] ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addResponseModal<?= $signalement['id_signalement'] ?>">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </td>
                            </tr>
                            
                            
                            <div class="modal fade" id="viewSignalementModal<?= $signalement['id_signalement'] ?>" tabindex="-1" aria-labelledby="viewSignalementModalLabel<?= $signalement['id_signalement'] ?>" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="viewSignalementModalLabel<?= $signalement['id_signalement'] ?>">Détails du signalement #<?= $signalement['id_signalement'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p><strong>Type:</strong> <?= ucfirst(str_replace('_', ' ', $signalement['type'])) ?></p>
                                                    <p><strong>Urgence:</strong> <span class="badge <?= $urgence_badge ?>"><?= ucfirst($signalement['urgence']) ?></span></p>
                                                    <p><strong>Date:</strong> <?= date('d/m/Y H:i', strtotime($signalement['date_signalement'])) ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Entreprise:</strong> <?= htmlspecialchars($signalement['nom_entreprise']) ?></p>
                                                    <p><strong>Salarié:</strong> <?= $signalement['anonyme'] ? '<em>Anonyme</em>' : htmlspecialchars($signalement['nom_salarie']) ?></p>
                                                    <p><strong>Email:</strong> <?= $signalement['anonyme'] ? '<em>Anonyme</em>' : htmlspecialchars($signalement['email_salarie']) ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="card mb-3">
                                                <div class="card-header">Contenu du signalement</div>
                                                <div class="card-body">
                                                    <p><?= nl2br(htmlspecialchars($signalement['contenu'])) ?></p>
                                                </div>
                                            </div>
                                            
                                            <?php
                                            
                                            $sql_reponses = "SELECT * FROM signalements_reponses WHERE id_signalement = ? ORDER BY date_reponse ASC";
                                            $stmt_reponses = $conn->prepare($sql_reponses);
                                            $stmt_reponses->execute([$signalement['id_signalement']]);
                                            $reponses = $stmt_reponses->fetchAll(PDO::FETCH_ASSOC);
                                            ?>
                                            
                                            <?php if (!empty($reponses)): ?>
                                            <div class="card">
                                                <div class="card-header">Réponses</div>
                                                <div class="card-body">
                                                    <?php foreach ($reponses as $reponse): ?>
                                                    <div class="mb-3 pb-3 border-bottom">
                                                        <small class="text-muted"><?= date('d/m/Y H:i', strtotime($reponse['date_reponse'])) ?></small>
                                                        <p class="mb-0"><?= nl2br(htmlspecialchars($reponse['contenu'])) ?></p>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <div class="alert alert-info">Aucune réponse pour ce signalement.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#changeStatusModal<?= $signalement['id_signalement'] ?>">Changer le statut</button>
                                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addResponseModal<?= $signalement['id_signalement'] ?>">Ajouter une réponse</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                           
                            <div class="modal fade" id="changeStatusModal<?= $signalement['id_signalement'] ?>" tabindex="-1" aria-labelledby="changeStatusModalLabel<?= $signalement['id_signalement'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="changeStatusModalLabel<?= $signalement['id_signalement'] ?>">Changer le statut du signalement #<?= $signalement['id_signalement'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" id="id_signalement_status<?= $signalement['id_signalement'] ?>" value="<?= $signalement['id_signalement'] ?>">
                                            
                                            <div class="mb-3">
                                                <label for="nouveau_statut<?= $signalement['id_signalement'] ?>" class="form-label">Nouveau statut</label>
                                                <select class="form-select" id="nouveau_statut<?= $signalement['id_signalement'] ?>" required>
                                                    <option value="nouveau" <?= $signalement['statut'] === 'nouveau' ? 'selected' : '' ?>>Nouveau</option>
                                                    <option value="en_traitement" <?= $signalement['statut'] === 'en_traitement' ? 'selected' : '' ?>>En traitement</option>
                                                    <option value="traité" <?= $signalement['statut'] === 'traité' ? 'selected' : '' ?>>Traité</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="button" class="btn btn-primary" onclick="updateStatus(<?= $signalement['id_signalement'] ?>)">Enregistrer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            
                            <div class="modal fade" id="addResponseModal<?= $signalement['id_signalement'] ?>" tabindex="-1" aria-labelledby="addResponseModalLabel<?= $signalement['id_signalement'] ?>" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="addResponseModalLabel<?= $signalement['id_signalement'] ?>">Répondre au signalement #<?= $signalement['id_signalement'] ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" id="id_signalement_response<?= $signalement['id_signalement'] ?>" value="<?= $signalement['id_signalement'] ?>">
                                            
                                            <div class="mb-3">
                                                <label for="contenu<?= $signalement['id_signalement'] ?>" class="form-label">Votre réponse</label>
                                                <textarea class="form-control" id="contenu<?= $signalement['id_signalement'] ?>" rows="5" required></textarea>
                                            </div>
                                            
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> Cette réponse sera visible par le salarié qui a effectué le signalement.
                                                <?php if ($signalement['statut'] === 'nouveau'): ?>
                                                <br>Le statut du signalement passera automatiquement à "En traitement".
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                            <button type="button" class="btn btn-success" onclick="addResponse(<?= $signalement['id_signalement'] ?>)">Envoyer</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/French.json'
            },
            order: [[5, 'desc']] 
        });
    });
    
    
    function updateStatus(id) {
        const status = document.getElementById(`nouveau_statut${id}`).value;
        
        fetch('/business-care-api/api/signalements/edit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'update_status',
                id_signalement: id,
                nouveau_statut: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                
                $(`#changeStatusModal${id}`).modal('hide');
                
                
                showAlert('success', data.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('danger', data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Une erreur est survenue lors de la communication avec le serveur');
        });
    }
    
    
    function addResponse(id) {
        const contenu = document.getElementById(`contenu${id}`).value;
        
        if (!contenu.trim()) {
            showAlert('danger', 'Le contenu de la réponse ne peut pas être vide');
            return;
        }
        
        fetch('/business-care-api/api/signalements/edit.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'add_response',
                id_signalement: id,
                contenu: contenu
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
            
                $(`#addResponseModal${id}`).modal('hide');
                
                
                showAlert('success', data.message);
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('danger', data.message || 'Une erreur est survenue');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'Une erreur est survenue lors de la communication avec le serveur');
        });
    }
    
    // Fonction pour afficher des alertes
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
       
        const alertContainer = document.getElementById('alert-container');
        alertContainer.appendChild(alertDiv);
        
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
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
    // Récupérer les salariés actifs
    $result = callAPI("salarie/findAll.php");
    
    if ($result && isset($result['data']['salaries'])) {
        $salaries = $result['data']['salaries']; 
    } else {
        $salaries = [];
    }
    
    // Récupérer les salariés archivés
    $result = callAPI("salarie/findAllArchived.php");
    
    if ($result && isset($result['data']['salaries_archives'])) {
        $salaries_archives = $result['data']['salaries_archives']; 
    } else {
        $salaries_archives = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $salaries = [];
    $salaries_archives = [];
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Gestion des Salariés</h1>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i> Ajouter un salarié
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Onglets -->
    <ul class="nav nav-tabs mb-3" id="salariesTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="active-tab" data-bs-toggle="tab" data-bs-target="#active-salaries" 
                type="button" role="tab" aria-controls="active-salaries" aria-selected="true">
                Salariés actifs
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="archived-tab" data-bs-toggle="tab" data-bs-target="#archived-salaries" 
                type="button" role="tab" aria-controls="archived-salaries" aria-selected="false">
                Salariés archivés
            </button>
        </li>
    </ul>

    <div class="tab-content" id="salariesTabsContent">
        <!-- Onglet Salariés actifs -->
        <div class="tab-pane fade show active" id="active-salaries" role="tabpanel" aria-labelledby="active-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Entreprise</th>
                                    <th>Statut</th>
                                    <th>Date création</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($salaries)): ?>
                                    <?php foreach ($salaries as $salarie): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($salarie['nom'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($salarie['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($salarie['nom_entreprise'] ?? $salarie['entreprise_nom'] ?? 'N/A') ?></td>
                                            <td>
                                                <?php 
                                                $statut = $salarie['statut'] ?? 0;
                                                $badgeClass = $statut ? 'success' : 'danger';
                                                $statutLabel = $statut ? 'Actif' : 'Inactif';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= $statutLabel ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($salarie['created_at'] ?? 'N/A') ?></td>
                                            <td>
                                                <div class="d-flex gap-2 justify-content-end">
                                                    <?php if(isset($salarie['id_salarie'])): ?>
                                                        <a href="edit.php?id=<?= $salarie['id_salarie'] ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#archiveModal<?= $salarie['id_salarie'] ?>">
                                                            <i class="fas fa-archive"></i>
                                                        </button>

                                                        <!-- Modal d'archivage -->
                                                        <div class="modal fade" id="archiveModal<?= $salarie['id_salarie'] ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Archiver le salarié</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <p>Êtes-vous sûr de vouloir archiver le salarié "<?= htmlspecialchars($salarie['nom'] ?? 'N/A') ?>" ?</p>
                                                                        <p class="text-muted small">Cette action préservera l'historique du salarié.</p>
                                                                        
                                                                        <div class="mb-3">
                                                                            <label for="raison_archivage<?= $salarie['id_salarie'] ?>" class="form-label">Raison de l'archivage</label>
                                                                            <textarea class="form-control" id="raison_archivage<?= $salarie['id_salarie'] ?>" rows="3" 
                                                                                      placeholder="Précisez la raison de l'archivage..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                        <button type="button" class="btn btn-danger btn-archive-salarie" 
                                                                                data-id="<?= $salarie['id_salarie'] ?>"
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
                                                Aucun salarié trouvé
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
        
        <!-- Onglet Salariés archivés -->
        <div class="tab-pane fade" id="archived-salaries" role="tabpanel" aria-labelledby="archived-tab">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Email</th>
                                    <th>Entreprise</th>
                                    <th>Statut</th>
                                    <th>Date d'archivage</th>
                                    <th>Raison</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($salaries_archives)): ?>
                                    <?php foreach ($salaries_archives as $salarie): ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars($salarie['nom'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($salarie['email'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($salarie['nom_entreprise'] ?? 'Entreprise archivée') ?></td>
                                            <td>
                                                <?php 
                                                $statut = $salarie['statut'] ?? 0;
                                                $badgeClass = $statut ? 'success' : 'danger';
                                                $statutLabel = $statut ? 'Actif' : 'Inactif';
                                                ?>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= $statutLabel ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($salarie['date_archivage'] ?? 'N/A') ?></td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none"
                                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                                        title="<?= htmlspecialchars($salarie['raison_archivage'] ?? 'Non spécifié') ?>">
                                                    Voir la raison
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="fas fa-info-circle me-2"></i>
                                                Aucun salarié archivé trouvé
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
    // Initialisation des tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    
    // Gestion de l'archivage
    document.querySelectorAll('.btn-archive-salarie').forEach(btn => {
        btn.addEventListener('click', function() {
            // Empêcher l'action par défaut
            event.preventDefault();
            
            const id = this.getAttribute('data-id');
            const raison = document.getElementById('raison_archivage' + id).value || "Archivage manuel";
            
            console.log('=== DÉBUT ARCHIVAGE ===');
            console.log('ID:', id);
            console.log('Raison:', raison);
            
            // Indicateur de chargement
            document.body.style.cursor = 'wait';
            const buttonElement = this;
            buttonElement.disabled = true;
            
            // Créer l'objet de données
            const requestData = {
                id_salarie: parseInt(id),
                raison_archivage: raison
            };
            
            console.log('Données à envoyer:', JSON.stringify(requestData));
            console.log('URL:', 'http://localhost/business-care-api/api/salarie/archive.php');
            
            // Configuration de la requête
            const requestOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(requestData)
            };
            
            console.log('Options de requête:', requestOptions);
            
            // Envoyer la requête
            fetch('http://localhost/business-care-api/api/salarie/archive.php', requestOptions)
            .then(response => {
                console.log('Réponse reçue');
                console.log('Status:', response.status);
                console.log('Status Text:', response.statusText);
                console.log('Headers:', response.headers);
                
                // Lire le corps de la réponse
                return response.text().then(text => {
                    console.log('Réponse brute:', text);
                    
                    // Parser en JSON si possible
                    try {
                        const data = JSON.parse(text);
                        return {
                            ok: response.ok,
                            status: response.status,
                            data: data
                        };
                    } catch (e) {
                        console.error('Erreur parsing JSON:', e);
                        return {
                            ok: response.ok,
                            status: response.status,
                            data: { error: text }
                        };
                    }
                });
            })
            .then(result => {
                document.body.style.cursor = 'default';
                console.log('Résultat final:', result);
                
                if (result.ok && result.data.status) {
                    console.log('Succès!');
                    // Message de succès et rechargement
                    alert('Salarié archivé avec succès');
                    window.location.reload();
                } else {
                    console.error('Erreur:', result.data.message || 'Erreur inconnue');
                    alert('Erreur: ' + (result.data.message || 'Erreur inconnue'));
                    buttonElement.disabled = false;
                }
            })
            .catch(error => {
                document.body.style.cursor = 'default';
                console.error('Erreur catch:', error);
                alert('Erreur de connexion: ' + error.message);
                buttonElement.disabled = false;
            });
        });
    });
});
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
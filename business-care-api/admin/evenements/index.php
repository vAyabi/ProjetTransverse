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
   
    $result = callAPI("evenement/findAll.php");
    
    if ($result && isset($result['data']['evenements'])) {
        $evenements = $result['data']['evenements']; 
    } else {
        
        $evenements = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $evenements = [];
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Gestion des Événements</h1>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i> Nouvel événement
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Prestataire</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Capacité</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($evenements)): ?>
                            <?php foreach ($evenements as $event): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($event['titre'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($event['type_evenement'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($event['prestataire_nom'] ?? $event['nom_prestataire'] ?? 'N/A') ?></td>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($event['date_debut'] ?? 'now')) ?><br>
                                        <small class="text-muted">
                                            jusqu'au <?= date('d/m/Y H:i', strtotime($event['date_fin'] ?? 'now')) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <?php 
                                        $statut = $event['statut'] ?? 'programmé';
                                        $badgeClass = $statut === 'programmé' ? 'info' : 
                                            ($statut === 'en_cours' ? 'success' : 
                                            ($statut === 'terminé' ? 'secondary' : 'danger'));
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= htmlspecialchars($statut) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($event['capacite_max'] ?? '0') ?></td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if(isset($event['id_evenement'])): ?>
                                                <a href="update.php?id=<?= $event['id_evenement'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?= $event['id_evenement'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                               
                                                <div class="modal fade" id="deleteModal<?= $event['id_evenement'] ?>">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Voulez-vous vraiment supprimer l'événement "<?= htmlspecialchars($event['titre'] ?? 'N/A') ?>" ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <a href="delete.php?id=<?= $event['id_evenement'] ?>" 
                                                                   class="btn btn-danger">Supprimer</a>
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
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Aucun événement trouvé
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

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
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
    
    $result = callAPI("prestataire/findAll.php");
    
    if ($result && isset($result['data']['prestataires'])) {
        $prestataires = $result['data']['prestataires']; 
    } else {
        
        $prestataires = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $prestataires = [];
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Gestion des Prestataires</h1>
        <a href="add.php" class="btn btn-success">
            <i class="fas fa-plus me-2"></i> Nouveau prestataire
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
                            <th>Nom</th>
                            <th>Type prestation</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Tarif horaire</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($prestataires)): ?>
                            <?php foreach ($prestataires as $prestataire): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($prestataire['nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($prestataire['type_prestation'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($prestataire['email'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($prestataire['telephone'] ?? 'N/A') ?></td>
                                    <td><?= ($prestataire['tarif_horaire'] ?? '0') ?>€/h</td>
                                    <td>
                                        <?php 
                                        $statut = $prestataire['statut_validation'] ?? 'en_attente';
                                        $badgeClass = $statut === 'validé' ? 'success' : ($statut === 'en_attente' ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= htmlspecialchars($statut) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if(isset($prestataire['id_prestataire'])): ?>
                                                <?php if(($prestataire['statut_validation'] ?? '') === 'en_attente'): ?>
                                                    <a href="validate.php?id=<?= $prestataire['id_prestataire'] ?>" 
                                                       class="btn btn-sm btn-success" title="Valider">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="edit.php?id=<?= $prestataire['id_prestataire'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#deleteModal<?= $prestataire['id_prestataire'] ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>

                                                
                                                <div class="modal fade" id="deleteModal<?= $prestataire['id_prestataire'] ?>">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirmer la suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Voulez-vous vraiment supprimer le prestataire "<?= htmlspecialchars($prestataire['nom'] ?? 'N/A') ?>" ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <a href="delete.php?id=<?= $prestataire['id_prestataire'] ?>" 
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
                                        Aucun prestataire trouvé
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
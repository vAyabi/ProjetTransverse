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
    
    $result = callAPI("admin/findAll.php");
    
    if ($result && isset($result['data']['admins'])) {
        $admins = $result['data']['admins']; 
    } else {
        // Pour déboguer
        // echo '<pre>'; print_r($result); echo '</pre>'; exit;
        $admins = [];
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
    $admins = [];
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Gestion des Administrateurs</h1>
        <a href="add.php" class="btn btn-success d-flex align-items-center">
            <i class="fas fa-plus me-2"></i> Nouvel administrateur
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

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Date création</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($admins)): ?>
                            <?php foreach($admins as $admin): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($admin['nom'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($admin['email'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?= htmlspecialchars($admin['role'] ?? 'admin') ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m/Y H:i', strtotime($admin['created_at'] ?? 'now')) ?></td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-end">
                                            <?php if(isset($admin['id_admin'])): ?>
                                                <a href="edit.php?id=<?= $admin['id_admin'] ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if($_SESSION['admin_id'] != $admin['id_admin']):  ?>
                                                    <button type="button" 
                                                            class="btn btn-sm btn-danger"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#deleteModal<?= $admin['id_admin'] ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>

                                                
                                                <div class="modal fade" id="deleteModal<?= $admin['id_admin'] ?>">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Confirmation de suppression</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                Êtes-vous sûr de vouloir supprimer l'administrateur "<?= htmlspecialchars($admin['nom'] ?? 'N/A') ?>" ?
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                <a href="delete.php?id=<?= $admin['id_admin'] ?>" 
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
                                <td colspan="5" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Aucun administrateur trouvé
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
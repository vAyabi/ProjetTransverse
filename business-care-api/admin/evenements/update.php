<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID non fourni";
    header('Location: index.php');
    exit();
}

$id = $_GET['id'];


function callAPI($endpoint, $method = 'GET', $data = null) {
    $apiUrl = "http://localhost/business-care-api/api/" . $endpoint;
    
    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $headers = ['Content-Type: application/json'];
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
    
    require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    
    $query = "SELECT id_prestataire, nom FROM prestataires WHERE statut_validation = 'validé'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $prestataires = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    
    $eventResult = callAPI("evenement/findOne.php?id=$id");
    
    if (!$eventResult || empty($eventResult['data'])) {
        $_SESSION['error'] = "Événement non trouvé";
        header('Location: index.php');
        exit();
    }
    
    $event = $eventResult['data'];
    
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'id_evenement' => $id,
            'titre' => $_POST['titre'],
            'description' => $_POST['description'],
            'type_evenement' => $_POST['type_evenement'],
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'],
            'capacite_max' => intval($_POST['capacite_max']),
            'statut' => $_POST['statut'],
            'id_prestataire' => intval($_POST['id_prestataire']),
            'id_entreprise' => $event['id_entreprise'] 
        ];
        
        $updateResult = callAPI("evenement/update.php", 'PUT', $data);
        
        if ($updateResult && isset($updateResult['status']) && $updateResult['status']) {
            $_SESSION['success'] = "Événement modifié avec succès";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception(isset($updateResult['message']) ? $updateResult['message'] : "Erreur lors de la modification de l'événement");
        }
    }
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Modifier l'événement</h4>
        </div>
        <div class="card-body">
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="mb-3">
                    <label class="form-label">Titre</label>
                    <input type="text" class="form-control" name="titre" 
                           value="<?= htmlspecialchars($event['titre']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($event['description']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type d'événement</label>
                    <select class="form-select" name="type_evenement" required>
                        <?php 
                        $types = ['webinar', 'conference', 'atelier', 'medical', 'sport'];
                        foreach($types as $type): ?>
                            <option value="<?= $type ?>" <?= $event['type_evenement'] == $type ? 'selected' : '' ?>>
                                <?= ucfirst($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date et heure de début</label>
                        <input type="datetime-local" class="form-control" name="date_debut" 
                               value="<?= date('Y-m-d\TH:i', strtotime($event['date_debut'])) ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date et heure de fin</label>
                        <input type="datetime-local" class="form-control" name="date_fin" 
                               value="<?= date('Y-m-d\TH:i', strtotime($event['date_fin'])) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Capacité maximale</label>
                    <input type="number" class="form-control" name="capacite_max" 
                           value="<?= htmlspecialchars($event['capacite_max']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="statut" required>
                        <?php 
                        $statuts = ['programmé', 'en_cours', 'terminé', 'annulé'];
                        foreach($statuts as $statut): ?>
                            <option value="<?= $statut ?>" <?= $event['statut'] == $statut ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $statut)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Prestataire</label>
                    <select class="form-select" name="id_prestataire" required>
                        <?php if(empty($prestataires)): ?>
                            <option value="">Aucun prestataire disponible</option>
                        <?php else: ?>
                            <?php foreach($prestataires as $prestataire): ?>
                                <option value="<?= $prestataire['id_prestataire'] ?>" 
                                        <?= $event['id_prestataire'] == $prestataire['id_prestataire'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prestataire['nom']) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Modifier l'événement</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
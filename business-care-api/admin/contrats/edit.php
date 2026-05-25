<?php
session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID du contrat invalide";
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

    
    $query = "SELECT * FROM contrats WHERE id_contrat = :id";
    $stmt = $conn->prepare($query);
    $stmt->execute([':id' => $id]);
    $contrat = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$contrat) {
        $_SESSION['error'] = "Contrat non trouvé";
        header('Location: index.php');
        exit();
    }

    
    $query = "SELECT id_entreprise, nom FROM entreprises WHERE statut = 1 ORDER BY nom";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $entreprises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
        
        $data = [
            'id_contrat' => intval($id),
            'id_entreprise' => intval($_POST['id_entreprise']),
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'],
            'montant_total' => floatval($_POST['montant_total']),
            'type_paiement' => $_POST['type_paiement'],
            'statut' => $contrat['statut'] 
        ];
        
        
        $result = callAPI("contrat/update.php", 'PUT', $data);
        
        if ($result && isset($result['status']) && $result['status']) {
            $_SESSION['success'] = "Contrat modifié avec succès";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception(isset($result['message']) ? $result['message'] : "Erreur lors de la modification du contrat");
        }
    }

} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Modifier le Contrat</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="id_entreprise" class="form-label">Entreprise</label>
                            <select class="form-select" id="id_entreprise" name="id_entreprise" required>
                                <option value="">Sélectionner une entreprise</option>
                                <?php foreach($entreprises as $entreprise): ?>
                                    <option value="<?= $entreprise['id_entreprise'] ?>" 
                                            <?= $contrat['id_entreprise'] == $entreprise['id_entreprise'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($entreprise['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="date_debut" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="date_debut" name="date_debut" 
                                       value="<?= $contrat['date_debut'] ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="date_fin" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="date_fin" name="date_fin" 
                                       value="<?= $contrat['date_fin'] ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="montant_total" class="form-label">Montant total</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="montant_total" name="montant_total" 
                                           value="<?= $contrat['montant_total'] ?>" step="0.01" required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="type_paiement" class="form-label">Type de paiement</label>
                                <select class="form-select" id="type_paiement" name="type_paiement" required>
                                    <option value="">Sélectionner le type</option>
                                    <option value="mensuel" <?= $contrat['type_paiement'] == 'mensuel' ? 'selected' : '' ?>>Mensuel</option>
                                    <option value="annuel" <?= $contrat['type_paiement'] == 'annuel' ? 'selected' : '' ?>>Annuel</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
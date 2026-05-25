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
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $result = callAPI("entreprise/findOne.php?id=$id");
        
        if (!$result || !isset($result['status']) || !$result['status'] || !isset($result['data'])) {
            $_SESSION['error'] = "Entreprise non trouvée";
            header('Location: index.php');
            exit();
        }
        
        $entreprise = $result['data'];
    }

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'id_entreprise' => intval($id),
            'nom' => $_POST['nom'],
            'email' => $_POST['email'],
            'telephone' => $_POST['telephone'],
            'adresse' => $_POST['adresse'],
            'type_formule' => $_POST['type_formule'],
            'statut' => intval($_POST['statut']),
            'siret' => $_POST['siret']
        ];
        
        $result = callAPI("entreprise/update.php", 'PUT', $data);
        
        if ($result && isset($result['status']) && $result['status']) {
            $_SESSION['success'] = "Entreprise modifiée avec succès";
            header('Location: index.php');
            exit();
        } else {
            $message = isset($result['message']) ? $result['message'] : "Erreur lors de la modification";
            throw new Exception($message);
        }
    }
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Modifier l'entreprise</h4>
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
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom de l'entreprise</label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($entreprise['nom'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="siret" class="form-label">SIRET</label>
                                <input type="text" class="form-control" id="siret" name="siret" 
                                       value="<?= htmlspecialchars($entreprise['siret'] ?? '') ?>" required
                                       pattern="[0-9]{14}" title="Le SIRET doit contenir 14 chiffres">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($entreprise['email'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" 
                                       value="<?= htmlspecialchars($entreprise['telephone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="type_formule" class="form-label">Type de formule</label>
                                <select class="form-select" id="type_formule" name="type_formule" required>
                                    <option value="starter" <?= isset($entreprise['type_formule']) && $entreprise['type_formule'] == 'starter' ? 'selected' : '' ?>>Starter</option>
                                    <option value="basic" <?= isset($entreprise['type_formule']) && $entreprise['type_formule'] == 'basic' ? 'selected' : '' ?>>Basic</option>
                                    <option value="premium" <?= isset($entreprise['type_formule']) && $entreprise['type_formule'] == 'premium' ? 'selected' : '' ?>>Premium</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select class="form-select" id="statut" name="statut" required>
                                    <option value="0" <?= isset($entreprise['statut']) && $entreprise['statut'] == 0 ? 'selected' : '' ?>>En attente</option>
                                    <option value="1" <?= isset($entreprise['statut']) && $entreprise['statut'] == 1 ? 'selected' : '' ?>>Actif</option>
                                    <option value="2" <?= isset($entreprise['statut']) && $entreprise['statut'] == 2 ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"><?= htmlspecialchars($entreprise['adresse'] ?? '') ?></textarea>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="index.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "ID du salarié manquant";
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
   
    $entreprisesResult = callAPI("entreprise/findAll.php");
    if ($entreprisesResult && isset($entreprisesResult['data']['entreprises'])) {
        
        $entreprises = array_filter($entreprisesResult['data']['entreprises'], function($entreprise) {
            return isset($entreprise['statut']) && $entreprise['statut'] == 1;
        });
    } else {
        $entreprises = [];
    }
    
    
    $salarieResult = callAPI("salarie/findOne.php?id=$id");
    if (!$salarieResult || !isset($salarieResult['status']) || !$salarieResult['status'] || !isset($salarieResult['data'])) {
        $_SESSION['error'] = "Salarié non trouvé";
        header('Location: index.php');
        exit();
    }
    
    $salarie = $salarieResult['data'];
    
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $data = [
            'id_salarie' => intval($id),
            'nom' => $_POST['nom'],
            'email' => $_POST['email'],
            'statut' => isset($_POST['statut']) ? 1 : 0,
            'id_entreprise' => intval($_POST['id_entreprise'])
        ];
        
       
        if (!empty($_POST['password'])) {
            $data['password'] = $_POST['password'];
        }
        
        $updateResult = callAPI("salarie/update.php", 'PUT', $data);
        
        if ($updateResult && isset($updateResult['status']) && $updateResult['status']) {
            $_SESSION['success'] = "Salarié modifié avec succès";
            header('Location: index.php');
            exit();
        } else {
            $message = isset($updateResult['message']) ? $updateResult['message'] : "Erreur lors de la modification";
            throw new Exception($message);
        }
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Modifier un salarié</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom complet</label>
                            <input type="text" class="form-control" id="nom" name="nom" 
                                   value="<?= htmlspecialchars($salarie['nom'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?= htmlspecialchars($salarie['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>

                        <div class="mb-3">
                            <label for="id_entreprise" class="form-label">Entreprise</label>
                            <select class="form-select" id="id_entreprise" name="id_entreprise" required>
                                <?php foreach($entreprises as $entreprise): ?>
                                    <option value="<?= $entreprise['id_entreprise'] ?>" 
                                            <?= ($entreprise['id_entreprise'] == ($salarie['id_entreprise'] ?? 0)) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($entreprise['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="statut" name="statut" 
                                   <?= isset($salarie['statut']) && $salarie['statut'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="statut">Compte actif</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Modifier le salarié</button>
                            <a href="index.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
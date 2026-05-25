<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
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
    
    if ($result && isset($result['data']['entreprises'])) {
        
        $entreprises = array_filter($result['data']['entreprises'], function($entreprise) {
            return isset($entreprise['statut']) && $entreprise['statut'] == 1;
        });
    } else {
        $entreprises = [];
    }
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des entreprises: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'nom' => $_POST['nom'],
            'email' => $_POST['email'],
            'password' => $_POST['password'], 
            'statut' => isset($_POST['statut']) ? 1 : 0,
            'id_entreprise' => intval($_POST['id_entreprise'])
        ];
        
        $result = callAPI("salarie/create.php", 'POST', $data);
        
        if ($result && isset($result['status']) && $result['status']) {
            $_SESSION['success'] = "Salarié ajouté avec succès";
            header('Location: index.php');
            exit();
        } else {
            $message = isset($result['message']) ? $result['message'] : "Erreur lors de l'ajout du salarié";
            throw new Exception($message);
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Ajouter un salarié</h4>
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
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="id_entreprise" class="form-label">Entreprise</label>
                            <select class="form-select" id="id_entreprise" name="id_entreprise" required>
                                <option value="">Sélectionner une entreprise</option>
                                <?php foreach($entreprises as $entreprise): ?>
                                    <option value="<?= $entreprise['id_entreprise'] ?>">
                                        <?= htmlspecialchars($entreprise['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="statut" name="statut" checked>
                            <label class="form-check-label" for="statut">Compte actif</label>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success">Ajouter le salarié</button>
                            <a href="index.php" class="btn btn-secondary">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
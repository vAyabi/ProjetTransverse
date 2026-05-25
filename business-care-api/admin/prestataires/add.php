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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        
        $data = [
            'nom' => $_POST['nom'],
            'specialite' => $_POST['specialite'],
            'email' => $_POST['email'],
            'password' => $_POST['password'], 
            'telephone' => $_POST['telephone'],
            'rib' => $_POST['rib'],
            'type_prestation' => $_POST['type_prestation'],
            'tarif_horaire' => floatval($_POST['tarif_horaire']),
            'statut_validation' => 'en_attente'
        ];
        
       
        $result = callAPI("prestataire/create.php", 'POST', $data);
        
        if ($result && isset($result['status']) && $result['status']) {
            $_SESSION['success'] = "Prestataire ajouté avec succès";
            header('Location: index.php');
            exit();
        } else {
            $message = isset($result['message']) ? $result['message'] : "Erreur lors de l'ajout du prestataire";
            throw new Exception($message);
        }
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Ajouter un prestataire</h4>
        </div>
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nom complet</label>
                        <input type="text" class="form-control" name="nom" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">RIB</label>
                        <input type="text" class="form-control" name="rib">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type de prestation</label>
                        <input type="text" class="form-control" name="type_prestation" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Spécialité</label>
                        <select class="form-select" name="specialite" required>
                            <option value="">Sélectionner une spécialité</option>
                            <option value="medical">Médical</option>
                            <option value="bien-etre">Bien-être</option>
                            <option value="sport">Sport</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tarif horaire (€)</label>
                        <input type="number" step="0.01" class="form-control" name="tarif_horaire" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" name="password" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Ajouter le prestataire</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
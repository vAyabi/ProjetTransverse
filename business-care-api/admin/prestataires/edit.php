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
    
    if(isset($_GET['id'])) {
        $id = $_GET['id'];
        $result = callAPI("Prestataire/findOne.php?id=$id");
        
        if (!$result || empty($result['data'])) {
            $_SESSION['error'] = "Prestataire non trouvé";
            header('Location: index.php');
            exit();
        }
        
        $prestataire = $result['data'];
    } else {
        $_SESSION['error'] = "ID non fourni";
        header('Location: index.php');
        exit();
    }
    
   
    if($_SERVER['REQUEST_METHOD'] == 'POST') {
       
        $data = [
            'id_prestataire' => $_GET['id'],
            'nom' => $_POST['nom'],
            'specialite' => $_POST['specialite'],
            'email' => $_POST['email'],
            'telephone' => $_POST['telephone'],
            'rib' => $_POST['rib'],
            'type_prestation' => $_POST['type_prestation'],
            'tarif_horaire' => $_POST['tarif_horaire'],
            'statut_validation' => $_POST['statut_validation']
        ];
        
        
        $result = callAPI("Prestataire/update.php", 'PUT', $data);
        
       
        if(!empty($_POST['password'])) {
            $passwordData = [
                'id_prestataire' => $_GET['id'],
                'password' => $_POST['password']
            ];
            
            $passwordResult = callAPI("Prestataire/updatePassword.php", 'PUT', $passwordData);
            
            if (!$passwordResult) {
                throw new Exception("Erreur lors de la mise à jour du mot de passe");
            }
        }
        
        if($result && !isset($result['error'])) {
            $_SESSION['success'] = "Prestataire modifié avec succès";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception("Erreur lors de la modification");
        }
    }
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">Modifier le prestataire</h4>
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
                        <label class="form-label">Nom complet</label>
                        <input type="text" class="form-control" name="nom" 
                               value="<?= htmlspecialchars($prestataire['nom']) ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= htmlspecialchars($prestataire['email']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Téléphone</label>
                        <input type="tel" class="form-control" name="telephone" 
                               value="<?= htmlspecialchars($prestataire['telephone']) ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">RIB</label>
                        <input type="text" class="form-control" name="rib" 
                               value="<?= htmlspecialchars($prestataire['rib']) ?>">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Type de prestation</label>
                        <input type="text" class="form-control" name="type_prestation" 
                               value="<?= htmlspecialchars($prestataire['type_prestation']) ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Spécialité</label>
                        <select class="form-select" name="specialite" required>
                            <option value="medical" <?= $prestataire['specialite'] == 'medical' ? 'selected' : '' ?>>
                                Médical</option>
                            <option value="bien-etre" <?= $prestataire['specialite'] == 'bien-etre' ? 'selected' : '' ?>>
                                Bien-être</option>
                            <option value="sport" <?= $prestataire['specialite'] == 'sport' ? 'selected' : '' ?>>
                                Sport</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Tarif horaire (€)</label>
                        <input type="number" step="0.01" class="form-control" name="tarif_horaire" 
                               value="<?= htmlspecialchars($prestataire['tarif_horaire']) ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Statut</label>
                        <select class="form-select" name="statut_validation" required>
                            <option value="en_attente" <?= $prestataire['statut_validation'] == 'en_attente' ? 'selected' : '' ?>>
                                En attente</option>
                            <option value="validé" <?= $prestataire['statut_validation'] == 'validé' ? 'selected' : '' ?>>
                                Validé</option>
                            <option value="refusé" <?= $prestataire['statut_validation'] == 'refusé' ? 'selected' : '' ?>>
                                Refusé</option>
                        </select>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">Modifier le prestataire</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
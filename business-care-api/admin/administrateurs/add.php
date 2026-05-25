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

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
    
        $data = [
            'nom' => $_POST['nom'],
            'email' => $_POST['email'],
            'password' => $_POST['password'],  
            'role' => 'admin'
        ];
        
    
        $result = callAPI("Admin/create.php", 'POST', $data);
        
        if ($result && isset($result['status']) && $result['status']) {
            $_SESSION['success'] = "Administrateur ajouté avec succès";
            header('Location: index.php');
            exit();
        } else {
            $errorMessage = isset($result['message']) ? $result['message'] : "Erreur lors de l'ajout de l'administrateur";
            throw new Exception($errorMessage);
        }
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-lg-6 mx-auto">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="card-title mb-0">Nouvel Administrateur</h5>
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
                            <label for="nom" class="form-label">Nom</label>
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

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary me-md-2">Annuler</a>
                            <button type="submit" class="btn btn-success">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
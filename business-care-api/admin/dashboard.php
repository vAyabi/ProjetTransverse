<?php
session_start();
if(!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: index.php');
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
    
    $stats = [
        'entreprises' => 0,
        'salaries' => 0,
        'prestataires' => 0,
        'evenements' => 0
    ];
    
    
    $result = callAPI('entreprise/findAll.php');
    if ($result && isset($result['data']['entreprises'])) {
        $stats['entreprises'] = count($result['data']['entreprises']);
    }
    
    
    $result = callAPI('salarie/findAll.php');
    if ($result && isset($result['data']['salaries'])) {
        $stats['salaries'] = count($result['data']['salaries']);
    }
    
    
    $result = callAPI('prestataire/findAll.php');
    if ($result && isset($result['data']['prestataires'])) {
        $stats['prestataires'] = count($result['data']['prestataires']);
    }
    
    
    $result = callAPI('evenement/findAll.php');
    if ($result && isset($result['data']['evenements'])) {
        $stats['evenements'] = count($result['data']['evenements']);
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des données: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0">Tableau de bord</h1>
    </div>

    
    <div class="row stats-grid g-4 mb-4">
        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Entreprises</h6>
                            <h2 class="mb-0"><?= $stats['entreprises'] ?></h2>
                        </div>
                        <div class="icon-circle bg-primary text-white">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Salariés</h6>
                            <h2 class="mb-0"><?= $stats['salaries'] ?></h2>
                        </div>
                        <div class="icon-circle bg-success text-white">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Prestataires</h6>
                            <h2 class="mb-0"><?= $stats['prestataires'] ?></h2>
                        </div>
                        <div class="icon-circle bg-warning text-white">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-xl-3 col-md-6">
            <div class="card stats-card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Événements</h6>
                            <h2 class="mb-0"><?= $stats['evenements'] ?></h2>
                        </div>
                        <div class="icon-circle bg-info text-white">
                            <i class="fas fa-calendar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Actions rapides</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="entreprise/add.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Nouvelle entreprise
                        </a>
                        <a href="evenements/add.php" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Nouvel événement
                        </a>
                        <a href="prestataires/add.php" class="btn btn-warning">
                            <i class="fas fa-plus me-1"></i> Nouveau prestataire
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
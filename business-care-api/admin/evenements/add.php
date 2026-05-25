<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}


try {
    
    $apiUrl = "http://localhost/business-care-api/api/Prestataire/findAll.php";

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new Exception("Erreur API: " . curl_error($ch));
    }

    $result = json_decode($response, true);
    
    

    if (isset($result['data']['prestataires'])) {
        $prestataires = array_filter($result['data']['prestataires'], function($p) {
            return isset($p['statut_validation']) && $p['statut_validation'] === 'validé';
        });
    } else {
        throw new Exception("Impossible de récupérer les prestataires");
    }
    
    
    
    curl_close($ch);
} catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    $prestataires = [];
}


$id_entreprise = $_SESSION['id_entreprise'] ?? 2; // Valeur par défaut pour test

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        
        $data = [
            'titre' => $_POST['titre'],
            'description' => $_POST['description'],
            'type_evenement' => $_POST['type_evenement'],
            'date_debut' => $_POST['date_debut'],
            'date_fin' => $_POST['date_fin'],
            'capacite_max' => intval($_POST['capacite_max']),
            'id_prestataire' => intval($_POST['id_prestataire']),
            'id_entreprise' => $id_entreprise
        ];

       
        $apiUrl = "http://localhost/business-care-api/api/evenement/create.php";

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            throw new Exception("Erreur API: " . curl_error($ch));
        }

        $result = json_decode($response, true);
       


        if ($httpCode === 201 && $result['status']) {
            $_SESSION['success'] = "Événement créé avec succès";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception($result['message'] ?? "Erreur lors de la création de l'événement");
        }

        curl_close($ch);
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}



include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">Nouvel événement</h4>
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
                    <input type="text" class="form-control" name="titre" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="description" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">Type d'événement</label>
                    <select class="form-select" name="type_evenement" required>
                        <option value="webinar">Webinar</option>
                        <option value="conference">Conférence</option>
                        <option value="atelier">Atelier</option>
                        <option value="medical">Médical</option>
                        <option value="sport">Sport</option>
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date et heure de début</label>
                        <input type="datetime-local" class="form-control" name="date_debut" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">Date et heure de fin</label>
                        <input type="datetime-local" class="form-control" name="date_fin" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Capacité maximale</label>
                    <input type="number" class="form-control" name="capacite_max" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Prestataire</label>
                    <select class="form-select" name="id_prestataire" required>
                        <option value="">Sélectionner un prestataire</option>
                        <?php foreach($prestataires as $prestataire): ?>
                            <option value="<?= $prestataire['id_prestataire'] ?>">
                                <?= htmlspecialchars($prestataire['nom']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">Créer l'événement</button>
                    <a href="index.php" class="btn btn-secondary">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>

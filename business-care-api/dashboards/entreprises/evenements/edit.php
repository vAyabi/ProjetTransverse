<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();


if(isset($_GET['id'])) {
    $stmt = $conn->prepare("SELECT * FROM evenements WHERE id_evenement = ? AND id_entreprise = ?");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $event = $stmt->fetch();

    if(!$event) {
        $_SESSION['error'] = "Événement non trouvé";
        header('Location: ../evenements.php');
        exit();
    }

    
    $stmt = $conn->prepare("SELECT * FROM prestataires WHERE statut_validation = 'validé'");
    $stmt->execute();
    $prestataires = $stmt->fetchAll();
}


if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        
        $date_debut = strtotime($_POST['date_debut']);
        $date_fin = strtotime($_POST['date_fin']);
        
        if($date_fin <= $date_debut) {
            throw new Exception("La date de fin doit être après la date de début");
        }

        $query = "UPDATE evenements SET 
                    titre = :titre,
                    description = :description,
                    type_evenement = :type_evenement,
                    date_debut = :date_debut,
                    date_fin = :date_fin,
                    capacite_max = :capacite_max,
                    id_prestataire = :id_prestataire
                 WHERE id_evenement = :id AND id_entreprise = :id_entreprise";

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([
            ':titre' => $_POST['titre'],
            ':description' => $_POST['description'],
            ':type_evenement' => $_POST['type_evenement'],
            ':date_debut' => $_POST['date_debut'],
            ':date_fin' => $_POST['date_fin'],
            ':capacite_max' => !empty($_POST['capacite_max']) ? $_POST['capacite_max'] : null,
            ':id_prestataire' => $_POST['id_prestataire'],
            ':id' => $_POST['id_evenement'],
            ':id_entreprise' => $_SESSION['user_id']
        ]);

        if($result) {
            $_SESSION['success'] = "Événement modifié avec succès";
            header('Location: ../evenements.php');
            exit();
        } else {
            throw new Exception("Erreur lors de la modification");
        }

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Modifier l'événement</h5>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <input type="hidden" name="id_evenement" value="<?= $event['id_evenement'] ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Titre</label>
                                <input type="text" class="form-control" name="titre" 
                                       value="<?= htmlspecialchars($event['titre']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type</label>
                                <select class="form-select" name="type_evenement" required>
                                    <option value="webinar" <?= $event['type_evenement'] == 'webinar' ? 'selected' : '' ?>>
                                        Webinar
                                    </option>
                                    <option value="conference" <?= $event['type_evenement'] == 'conference' ? 'selected' : '' ?>>
                                        Conférence
                                    </option>
                                    <option value="atelier" <?= $event['type_evenement'] == 'atelier' ? 'selected' : '' ?>>
                                        Atelier
                                    </option>
                                    <option value="medical" <?= $event['type_evenement'] == 'medical' ? 'selected' : '' ?>>
                                        Médical
                                    </option>
                                    <option value="sport" <?= $event['type_evenement'] == 'sport' ? 'selected' : '' ?>>
                                        Sport
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Date de début</label>
                                <input type="datetime-local" class="form-control" name="date_debut" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($event['date_debut'])) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de fin</label>
                                <input type="datetime-local" class="form-control" name="date_fin" 
                                       value="<?= date('Y-m-d\TH:i', strtotime($event['date_fin'])) ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-8">
                                <label class="form-label">Prestataire</label>
                                <select class="form-select" name="id_prestataire" required>
                                    <?php foreach($prestataires as $prestataire): ?>
                                        <option value="<?= $prestataire['id_prestataire'] ?>" 
                                                <?= $event['id_prestataire'] == $prestataire['id_prestataire'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($prestataire['nom']) ?> 
                                            (<?= ucfirst($prestataire['specialite']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Capacité maximale</label>
                                <input type="number" class="form-control" name="capacite_max" 
                                       value="<?= $event['capacite_max'] ?>">
                                <small class="text-muted">Laisser vide si illimité</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($event['description']) ?></textarea>
                        </div>

                        <div class="text-end">
                            <a href="../evenements.php" class="btn btn-secondary">Annuler</a>
                            <button type="submit" class="btn btn-primary">Sauvegarder</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
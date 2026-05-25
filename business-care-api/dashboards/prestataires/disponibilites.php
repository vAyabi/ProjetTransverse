<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Gestion de mes disponibilités";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';

$id_prestataire = $_SESSION['user_id'];

// Récupérer toutes les disponibilités
$sql = "SELECT * FROM disponibilites_prestataires WHERE id_prestataire = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id_prestataire]);
$disponibilites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organiser les disponibilités
$dispo_hebdo = [
    'lundi' => [],
    'mardi' => [],
    'mercredi' => [],
    'jeudi' => [],
    'vendredi' => [],
    'samedi' => [],
    'dimanche' => []
];
$dispo_specifiques = [];

// Traiter les résultats
foreach ($disponibilites as $dispo) {
    if (!empty($dispo['jour_semaine'])) {
        // Normaliser le jour de la semaine (tout en minuscules)
        $jour = strtolower(trim($dispo['jour_semaine']));
        
        // Vérifier si c'est un jour valide
        if (isset($dispo_hebdo[$jour])) {
            $dispo_hebdo[$jour][] = [
                'id' => $dispo['id_disponibilite'],
                'debut' => $dispo['heure_debut'],
                'fin' => $dispo['heure_fin']
            ];
        }
    } 
    elseif (!empty($dispo['date_specifique'])) {
        $date = $dispo['date_specifique'];
        if (!isset($dispo_specifiques[$date])) {
            $dispo_specifiques[$date] = [];
        }
        $dispo_specifiques[$date][] = [
            'id' => $dispo['id_disponibilite'],
            'debut' => $dispo['heure_debut'],
            'fin' => $dispo['heure_fin']
        ];
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajout d'une disponibilité
    if (isset($_POST['ajouter_disponibilite'])) {
        $type = $_POST['type_disponibilite'];
        $heure_debut = $_POST['heure_debut'];
        $heure_fin = $_POST['heure_fin'];
        
        try {
            if ($type === 'hebdo') {
                // Disponibilité hebdomadaire
                $jour_semaine = $_POST['jour_ou_date'];
                
                // Vérification que le jour n'est pas vide
                if (empty($jour_semaine)) {
                    throw new Exception("Le jour de la semaine ne peut pas être vide");
                }
                
                // Requête SQL pour insérer une disponibilité hebdomadaire
                $sql = "INSERT INTO disponibilites_prestataires 
                       (id_prestataire, jour_semaine, date_specifique, heure_debut, heure_fin) 
                       VALUES (?, ?, NULL, ?, ?)";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$id_prestataire, $jour_semaine, $heure_debut, $heure_fin]);
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion: " . implode(", ", $stmt->errorInfo()));
                }
            } 
            else {
                // Disponibilité spécifique
                $date_specifique = $_POST['jour_ou_date'];
                
                // Vérification que la date n'est pas vide
                if (empty($date_specifique)) {
                    throw new Exception("La date spécifique ne peut pas être vide");
                }
                
                $sql = "INSERT INTO disponibilites_prestataires 
                       (id_prestataire, jour_semaine, date_specifique, heure_debut, heure_fin) 
                       VALUES (?, NULL, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $result = $stmt->execute([$id_prestataire, $date_specifique, $heure_debut, $heure_fin]);
                
                if (!$result) {
                    throw new Exception("Erreur lors de l'insertion: " . implode(", ", $stmt->errorInfo()));
                }
            }
            
            // Rediriger après succès
            header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
            exit();
        } 
        catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
    
    // Suppression d'une disponibilité
    if (isset($_POST['supprimer_disponibilite'])) {
        $id_disponibilite = $_POST['id_disponibilite'];
        
        $sql = "DELETE FROM disponibilites_prestataires WHERE id_disponibilite = ? AND id_prestataire = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id_disponibilite, $id_prestataire]);
        
        header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
        exit();
    }
}
?>

<div class="container">
    <h1>Gestion de mes disponibilités</h1>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        Disponibilité ajoutée avec succès.
    </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-success">
        Disponibilité supprimée avec succès.
    </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
    <div class="alert alert-danger">
        <?= $error_message ?>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Mon calendrier hebdomadaire</h5>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#ajoutDispoModal">
                <i class="fas fa-plus"></i> Ajouter une disponibilité
            </button>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Jour</th>
                        <th>Horaires</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dispo_hebdo as $jour => $horaires): ?>
                    <tr>
                        <td><?= ucfirst($jour) ?></td>
                        <td>
                            <?php if (empty($horaires)): ?>
                                <span class="text-muted">Aucune disponibilité</span>
                            <?php else: ?>
                                <?php foreach ($horaires as $horaire): ?>
                                    <div class="badge bg-success mb-1">
                                        <?= substr($horaire['debut'], 0, 5) ?> - <?= substr($horaire['fin'], 0, 5) ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="id_disponibilite" value="<?= $horaire['id'] ?>">
                                            <button type="submit" name="supprimer_disponibilite" class="btn btn-sm btn-danger ms-2" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-success" onclick="preparerAjoutDispo('hebdo', '<?= $jour ?>')">
                                <i class="fas fa-plus"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Dates spécifiques</h5>
        </div>
        <div class="card-body">
            <?php if (empty($dispo_specifiques)): ?>
                <p class="text-muted">Aucune date spécifique définie. Utilisez le bouton "Ajouter une disponibilité" pour ajouter une date particulière.</p>
            <?php else: ?>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Horaires</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dispo_specifiques as $date => $horaires): ?>
                        <tr>
                            <td><?= date('d/m/Y', strtotime($date)) ?></td>
                            <td>
                                <?php foreach ($horaires as $horaire): ?>
                                    <div class="badge bg-primary mb-1">
                                        <?= substr($horaire['debut'], 0, 5) ?> - <?= substr($horaire['fin'], 0, 5) ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="id_disponibilite" value="<?= $horaire['id'] ?>">
                                            <button type="submit" name="supprimer_disponibilite" class="btn btn-sm btn-danger ms-2" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette disponibilité ?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary" onclick="preparerAjoutDispo('specifique', '<?= $date ?>')">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>


<div class="modal fade" id="ajoutDispoModal" tabindex="-1" aria-labelledby="ajoutDispoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ajoutDispoModalLabel">Ajouter une disponibilité</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de disponibilité</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type_disponibilite" id="type_hebdo" value="hebdo" checked onchange="toggleTypeDisponibilite()">
                            <label class="form-check-label" for="type_hebdo">
                                Horaire hebdomadaire (se répète chaque semaine)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type_disponibilite" id="type_specifique" value="specifique" onchange="toggleTypeDisponibilite()">
                            <label class="form-check-label" for="type_specifique">
                                Date spécifique (ponctuelle)
                            </label>
                        </div>
                    </div>
                    
                    <div id="section_hebdo" class="mb-3">
                        <label for="jour_semaine" class="form-label">Jour de la semaine</label>
                        <select class="form-select" id="jour_semaine" name="jour_ou_date">
                            <option value="lundi">Lundi</option>
                            <option value="mardi">Mardi</option>
                            <option value="mercredi">Mercredi</option>
                            <option value="jeudi">Jeudi</option>
                            <option value="vendredi">Vendredi</option>
                            <option value="samedi">Samedi</option>
                            <option value="dimanche">Dimanche</option>
                        </select>
                    </div>
                    
                    <div id="section_specifique" class="mb-3" style="display: none;">
                        <label for="date_specifique" class="form-label">Date spécifique</label>
                        <input type="date" class="form-control" id="date_specifique" name="jour_ou_date">
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col">
                            <label for="heure_debut" class="form-label">Heure de début</label>
                            <input type="time" class="form-control" id="heure_debut" name="heure_debut" required>
                        </div>
                        <div class="col">
                            <label for="heure_fin" class="form-label">Heure de fin</label>
                            <input type="time" class="form-control" id="heure_fin" name="heure_fin" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" name="ajouter_disponibilite" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleTypeDisponibilite() {
    const typeHebdo = document.getElementById('type_hebdo').checked;
    document.getElementById('section_hebdo').style.display = typeHebdo ? 'block' : 'none';
    document.getElementById('section_specifique').style.display = typeHebdo ? 'none' : 'block';
    
    // Réinitialiser et activer le bon champ
    if (typeHebdo) {
        document.getElementById('date_specifique').value = '';
        document.getElementById('date_specifique').removeAttribute('name');
        document.getElementById('jour_semaine').setAttribute('name', 'jour_ou_date');
    } else {
        document.getElementById('jour_semaine').removeAttribute('name');
        document.getElementById('date_specifique').setAttribute('name', 'jour_ou_date');
    }
}

function preparerAjoutDispo(type, valeur) {
   
    const modal = new bootstrap.Modal(document.getElementById('ajoutDispoModal'));
    modal.show();
    
    // Préremplir les champs
    if (type === 'hebdo') {
        document.getElementById('type_hebdo').checked = true;
        document.getElementById('type_specifique').checked = false;
        document.getElementById('jour_semaine').value = valeur;
        document.getElementById('jour_semaine').setAttribute('name', 'jour_ou_date');
        document.getElementById('date_specifique').removeAttribute('name');
        toggleTypeDisponibilite();
    } else {
        document.getElementById('type_hebdo').checked = false;
        document.getElementById('type_specifique').checked = true;
        document.getElementById('date_specifique').value = valeur;
        document.getElementById('date_specifique').setAttribute('name', 'jour_ou_date');
        document.getElementById('jour_semaine').removeAttribute('name');
        toggleTypeDisponibilite();
    }
}

// S'assurer que le bon champ a le bon nom au chargement
document.addEventListener('DOMContentLoaded', function() {
    toggleTypeDisponibilite();
});
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
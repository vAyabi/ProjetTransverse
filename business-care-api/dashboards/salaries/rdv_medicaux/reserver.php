<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
$page_title = "Réserver un RDV médical";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';

$id_salarie = $_SESSION['user_id'];

// Récupérer le type de formule de l'entreprise
$sql_entreprise = "SELECT type_formule FROM entreprises WHERE id_entreprise = (SELECT id_entreprise FROM salaries WHERE id_salarie = ?)";
$stmt_entreprise = $conn->prepare($sql_entreprise);
$stmt_entreprise->execute([$id_salarie]);
$entreprise = $stmt_entreprise->fetch(PDO::FETCH_ASSOC);

// Déterminer le quota selon la formule
$quota_mensuel = 1; // Par défaut (Starter)
if ($entreprise['type_formule'] == 'basic') {
    $quota_mensuel = 2;
} elseif ($entreprise['type_formule'] == 'premium') {
    $quota_mensuel = 3;
}

// Vérifier le quota restant pour le mois en cours
$mois_actuel = date('n');
$annee_actuelle = date('Y');
$sql_quota = "SELECT quota_disponible FROM quota_rdv_medicaux WHERE id_salarie = ? AND mois = ? AND annee = ?";
$stmt_quota = $conn->prepare($sql_quota);
$stmt_quota->execute([$id_salarie, $mois_actuel, $annee_actuelle]);
$quota = $stmt_quota->fetch(PDO::FETCH_ASSOC);

if (!$quota) {
    $sql_insert_quota = "INSERT INTO quota_rdv_medicaux (id_salarie, mois, annee, quota_disponible, quota_total) VALUES (?, ?, ?, ?, ?)";
    $stmt_insert_quota = $conn->prepare($sql_insert_quota);
    $stmt_insert_quota->execute([$id_salarie, $mois_actuel, $annee_actuelle, $quota_mensuel, $quota_mensuel]);
    $quota_restant = $quota_mensuel;
} else {
    $quota_restant = $quota['quota_disponible'];
}

// Récupérer les prestataires médicaux
$sql_prestataires = "SELECT id_prestataire, nom, specialite, type_prestation FROM prestataires WHERE specialite = 'medical' AND statut_validation = 'validé'";
$stmt_prestataires = $conn->prepare($sql_prestataires);
$stmt_prestataires->execute();
$prestataires = $stmt_prestataires->fetchAll(PDO::FETCH_ASSOC);

// Variables pour gérer le formulaire
$selected_prestataire = isset($_POST['prestataire']) ? $_POST['prestataire'] : '';
$dates = [];
$selected_date = isset($_POST['date']) ? $_POST['date'] : '';
$heures = [];

// Si un prestataire est sélectionné
if (!empty($selected_prestataire)) {
    // Correspondance des jours français/anglais
    $jours = [
        'lundi' => 'Monday',
        'mardi' => 'Tuesday',
        'mercredi' => 'Wednesday',
        'jeudi' => 'Thursday',
        'vendredi' => 'Friday',
        'samedi' => 'Saturday',
        'dimanche' => 'Sunday'
    ];
    
    // 1. Récupérer les disponibilités hebdomadaires
    $sql_hebdo = "SELECT DISTINCT jour_semaine, heure_debut, heure_fin 
                 FROM disponibilites_prestataires 
                 WHERE id_prestataire = ? AND jour_semaine IS NOT NULL";
    $stmt_hebdo = $conn->prepare($sql_hebdo);
    $stmt_hebdo->execute([$selected_prestataire]);
    $dispos_hebdo = $stmt_hebdo->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Récupérer les disponibilités spécifiques
    $sql_specifique = "SELECT date_specifique, heure_debut, heure_fin 
                      FROM disponibilites_prestataires 
                      WHERE id_prestataire = ? AND date_specifique IS NOT NULL 
                      AND date_specifique >= CURDATE() 
                      ORDER BY date_specifique";
    $stmt_specifique = $conn->prepare($sql_specifique);
    $stmt_specifique->execute([$selected_prestataire]);
    $dispos_specifiques = $stmt_specifique->fetchAll(PDO::FETCH_ASSOC);
    
    // Traiter les disponibilités hebdomadaires pour la semaine à venir
    if (!empty($dispos_hebdo)) {
        // Stocker les disponibilités par jour de la semaine
        $disponibilites_par_jour = [];
        foreach ($dispos_hebdo as $dispo) {
            $disponibilites_par_jour[$dispo['jour_semaine']] = [
                'debut' => $dispo['heure_debut'],
                'fin' => $dispo['heure_fin']
            ];
        }
        
        // Générer les dates pour la semaine à venir uniquement
        $aujourd_hui = new DateTime();
        $fin_semaine = clone $aujourd_hui;
        $fin_semaine->modify('+7 days');
        
        $date_courante = clone $aujourd_hui;
        
        // On ne prend que 1 occurrence de chaque jour de la semaine
        $jours_traites = [];
        
        while ($date_courante <= $fin_semaine) {
            $jour_semaine_en = $date_courante->format('l');
            $jour_semaine_fr = array_search($jour_semaine_en, $jours);
            
            // Si ce jour est disponible pour le prestataire ET qu'on ne l'a pas déjà traité
            if ($jour_semaine_fr && isset($disponibilites_par_jour[$jour_semaine_fr]) && !in_array($jour_semaine_fr, $jours_traites)) {
                $date_str = $date_courante->format('Y-m-d');
                
                // Ajouter cette date aux dates disponibles
                $dates[$date_str] = [
                    'display' => $date_courante->format('d/m/Y') . ' (' . ucfirst($jour_semaine_fr) . ')',
                    'debut' => $disponibilites_par_jour[$jour_semaine_fr]['debut'],
                    'fin' => $disponibilites_par_jour[$jour_semaine_fr]['fin']
                ];
                
                // Marquer ce jour comme traité
                $jours_traites[] = $jour_semaine_fr;
            }
            
            // Passer au jour suivant
            $date_courante->modify('+1 day');
        }
    }
    
    // Ajouter les disponibilités pour dates spécifiques
    foreach ($dispos_specifiques as $dispo) {
        $date_str = $dispo['date_specifique'];
        $date_obj = new DateTime($date_str);
        $jour_semaine_fr = strtolower($date_obj->format('l'));
        $jour_semaine_fr = array_search($jour_semaine_fr, $jours);
        
        $dates[$date_str] = [
            'display' => $date_obj->format('d/m/Y') . ' (Spécifique)',
            'debut' => $dispo['heure_debut'],
            'fin' => $dispo['heure_fin']
        ];
    }
    
    // Trier les dates par ordre chronologique
    ksort($dates);
    
    // Si une date est sélectionnée, générer les heures disponibles
    if (!empty($selected_date) && isset($dates[$selected_date])) {
        $heure_debut = strtotime($dates[$selected_date]['debut']);
        $heure_fin = strtotime($dates[$selected_date]['fin']);
        
        // Créer des créneaux de 30 minutes
        for ($time = $heure_debut; $time < $heure_fin; $time += 30 * 60) {
            $heure_str = date('H:i', $time);
            
            // Vérifier si ce créneau horaire est déjà pris
            $date_heure = $selected_date . ' ' . $heure_str . ':00';
            $sql_check_creneau = "SELECT COUNT(*) FROM rendez_vous_medicaux 
                                 WHERE id_prestataire = ? AND date_heure = ? AND statut = 'programmé'";
            $stmt_check_creneau = $conn->prepare($sql_check_creneau);
            $stmt_check_creneau->execute([$selected_prestataire, $date_heure]);
            
            // Si le créneau n'est pas déjà pris, on l'ajoute aux heures disponibles
            if ($stmt_check_creneau->fetchColumn() == 0) {
                $heures[] = $heure_str;
            }
        }
    }
}

// Traitement de la réservation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserver'])) {
    $id_prestataire = $_POST['prestataire'];
    $date = $_POST['date'];
    $heure = $_POST['heure'];
    $date_heure = $date . ' ' . $heure . ':00';
    $type = $_POST['type'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $hors_quota = isset($_POST['hors_quota']) ? 1 : 0;
    
    $error = false;
    $error_message = '';
    
    // Vérifications
    if (strtotime($date_heure) < time()) {
        $error = true;
        $error_message = "Vous ne pouvez pas réserver un rendez-vous dans le passé.";
    } elseif ($hors_quota == 0 && $quota_restant <= 0) {
        $error = true;
        $error_message = "Votre quota mensuel est épuisé. Veuillez sélectionner l'option 'RDV supplémentaire (payant)'.";
    } else {
        // Vérifier si le créneau est déjà pris
        $stmt = $conn->prepare("SELECT COUNT(*) FROM rendez_vous_medicaux WHERE id_prestataire = ? AND date_heure = ? AND statut = 'programmé'");
        $stmt->execute([$id_prestataire, $date_heure]);
        
        if ($stmt->fetchColumn() > 0) {
            $error = true;
            $error_message = "Ce créneau est déjà réservé. Veuillez choisir un autre horaire.";
        }
    }
    
    if (!$error) {
        try {
            $conn->beginTransaction();
            
            // Générer un nom de salle Jitsi si c'est une visioconférence
            $jitsi_room_name = null;
            if ($type === 'visioconference') {
                // Générer un nom de salle unique et sécurisé
                $jitsi_room_name = 'BC-' . hash('sha256', $id_salarie . '-' . $id_prestataire . '-' . time() . '-' . mt_rand());
                // Tronquer pour obtenir une longueur raisonnable
                $jitsi_room_name = substr($jitsi_room_name, 0, 20);
            }
            
            // Insérer le rendez-vous
            $stmt = $conn->prepare("INSERT INTO rendez_vous_medicaux (id_salarie, id_prestataire, date_heure, type, notes, hors_quota, jitsi_room_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id_salarie, $id_prestataire, $date_heure, $type, $notes, $hors_quota, $jitsi_room_name]);
            
            // Mettre à jour le quota si nécessaire
            if ($hors_quota == 0) {
                $stmt = $conn->prepare("UPDATE quota_rdv_medicaux SET quota_disponible = quota_disponible - 1 WHERE id_salarie = ? AND mois = ? AND annee = ?");
                $stmt->execute([$id_salarie, $mois_actuel, $annee_actuelle]);
            }
            
            $conn->commit();
            header('Location: /business-care-api/dashboards/salaries/rdv_medicaux/index.php?success=1');
            exit();
        } catch (Exception $e) {
            $conn->rollBack();
            $error = true;
            $error_message = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>

<div class="container">
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="/business-care-api/dashboards/salaries/index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="/business-care-api/dashboards/salaries/rdv_medicaux/index.php">RDV Médicaux</a></li>
                    <li class="breadcrumb-item active">Réserver un RDV</li>
                </ol>
            </nav>
        </div>
    </div>

    <h1>Réserver un rendez-vous médical</h1>
    
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Il vous reste <?= $quota_restant ?> RDV médical(aux) gratuit(s) pour ce mois-ci.
    </div>
    
    <?php if(isset($error) && $error): ?>
    <div class="alert alert-danger">
        <?= $error_message ?>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Nouveau rendez-vous médical</h5>
            
            <form method="post" action="">
                <!-- Étape 1: Choisir un praticien -->
                <div class="mb-3">
                    <label for="prestataire" class="form-label">Choisir un praticien</label>
                    <select class="form-select" id="prestataire" name="prestataire" required onchange="this.form.submit()">
                        <option value="">Sélectionnez un praticien</option>
                        <?php foreach($prestataires as $prestataire): ?>
                        <option value="<?= $prestataire['id_prestataire'] ?>" <?= ($selected_prestataire == $prestataire['id_prestataire']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prestataire['nom']) ?> - <?= htmlspecialchars($prestataire['type_prestation']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <?php if(!empty($selected_prestataire)): ?>
                    <?php if(empty($dates)): ?>
                    <div class="alert alert-warning">
                        Ce prestataire n'a pas défini ses disponibilités. Veuillez choisir un autre prestataire ou réessayer plus tard.
                    </div>
                    <?php else: ?>
                    <!-- Étape 2: Choisir une date -->
                    <div class="mb-3">
                        <label for="date" class="form-label">Date</label>
                        <select class="form-select" id="date" name="date" required onchange="this.form.submit()">
                            <option value="">Sélectionnez une date</option>
                            <?php foreach($dates as $date => $info): ?>
                            <option value="<?= $date ?>" <?= ($selected_date == $date) ? 'selected' : '' ?>>
                                <?= $info['display'] ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if(!empty($selected_date) && !empty($heures)): ?>
                    <!-- Étape 3: Choisir une heure -->
                    <div class="mb-3">
                        <label for="heure" class="form-label">Heure</label>
                        <select class="form-select" id="heure" name="heure" required>
                            <option value="">Sélectionnez une heure</option>
                            <?php foreach($heures as $heure): ?>
                            <option value="<?= $heure ?>"><?= $heure ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Les rendez-vous sont disponibles de <?= substr($dates[$selected_date]['debut'], 0, 5) ?> à <?= substr($dates[$selected_date]['fin'], 0, 5) ?></small>
                    </div>
                    
                    <!-- Type de consultation -->
                    <div class="mb-3">
                        <label class="form-label">Type de consultation</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_presentiel" value="presentiel" checked>
                            <label class="form-check-label" for="type_presentiel">
                                Présentiel (dans nos locaux)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="type" id="type_visio" value="visioconference">
                            <label class="form-check-label" for="type_visio">
                                Visioconférence
                            </label>
                            <small class="form-text text-muted d-block mt-1">
                                La visioconférence sera accessible 15 minutes avant l'heure du rendez-vous
                            </small>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (facultatif)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        <div class="form-text">Ces informations resteront confidentielles et ne seront partagées qu'avec le praticien.</div>
                    </div>
                    
                    <!-- Option hors quota si nécessaire -->
                    <?php if($quota_restant <= 0): ?>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="hors_quota" name="hors_quota" checked>
                        <label class="form-check-label" for="hors_quota">
                            Je comprends que ce rendez-vous sera facturé <?= $entreprise['type_formule'] == 'premium' ? '50' : '75' ?>€ car j'ai épuisé mon quota mensuel gratuit
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Boutons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/business-care-api/dashboards/salaries/rdv_medicaux/index.php" class="btn btn-outline-secondary">Annuler</a>
                        <button type="submit" name="reserver" class="btn btn-primary">Réserver le rendez-vous</button>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
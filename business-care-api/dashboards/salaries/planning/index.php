<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';

// Vérifier si l'utilisateur est connecté en tant que salarié
if (!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

$page_title = "Mon Planning";
include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/header_dashboard.php';
?>
<!-- FullCalendar Library -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/locales/fr.js"></script>

<?php
$db = new Database();
$conn = $db->getConnection();
$id_salarie = $_SESSION['user_id'];

// Récupérer les événements auxquels le salarié est inscrit
$sql_evenements = "
    SELECT e.*, ie.statut as statut_inscription
    FROM evenements e
    JOIN inscriptions_evenements ie ON e.id_evenement = ie.id_evenement
    WHERE ie.id_salarie = ?
    ORDER BY e.date_debut ASC
";
$stmt_evenements = $conn->prepare($sql_evenements);
$stmt_evenements->execute([$id_salarie]);
$evenements = $stmt_evenements->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les rendez-vous médicaux du salarié
$sql_rdv = "
    SELECT r.*, p.nom as nom_prestataire, p.specialite
    FROM rendez_vous_medicaux r
    JOIN prestataires p ON r.id_prestataire = p.id_prestataire
    WHERE r.id_salarie = ? AND r.statut != 'annulé'
    ORDER BY r.date_heure ASC
";
$stmt_rdv = $conn->prepare($sql_rdv);
$stmt_rdv->execute([$id_salarie]);
$rendez_vous = $stmt_rdv->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les événements des communautés
$sql_communautes = "
    SELECT ce.*, c.nom as nom_communaute, cp.statut
    FROM communautes_evenements ce
    JOIN communautes c ON ce.id_communaute = c.id_communaute
    JOIN communautes_participants cp ON ce.id_evenement_communaute = cp.id_evenement_communaute
    WHERE cp.id_salarie = ?
    ORDER BY ce.date_debut ASC
";
$stmt_communautes = $conn->prepare($sql_communautes);
$stmt_communautes->execute([$id_salarie]);
$evenements_communautes = $stmt_communautes->fetchAll(PDO::FETCH_ASSOC);

// Créer un tableau combiné de tous les événements pour affichage chronologique
$tous_evenements = [];

// Ajouter les événements d'entreprise
foreach ($evenements as $evt) {
    $tous_evenements[] = [
        'titre' => $evt['titre'],
        'type' => 'Événement ' . $evt['type_evenement'],
        'date' => $evt['date_debut'],
        'id' => $evt['id_evenement'],
        'lien' => '../evenements/details.php?id=' . $evt['id_evenement'],
        'classe' => 'bg-primary'
    ];
}

// Ajouter les rendez-vous médicaux
foreach ($rendez_vous as $rdv) {
    $tous_evenements[] = [
        'titre' => 'RDV ' . ucfirst($rdv['specialite']),
        'type' => 'RDV médical avec ' . $rdv['nom_prestataire'],
        'date' => $rdv['date_heure'],
        'id' => $rdv['id_rdv'],
        'lien' => '../rdv_medicaux/details.php?id=' . $rdv['id_rdv'],
        'classe' => 'bg-warning'
    ];
}

// Ajouter les événements communautaires
foreach ($evenements_communautes as $evt) {
    $tous_evenements[] = [
        'titre' => $evt['titre'],
        'type' => 'Communauté: ' . $evt['nom_communaute'],
        'date' => $evt['date_debut'],
        'id' => $evt['id_evenement_communaute'],
        'lien' => '../communautes/evenement.php?id=' . $evt['id_evenement_communaute'],
        'classe' => 'bg-success'
    ];
}

// Trier tous les événements par date
usort($tous_evenements, function($a, $b) {
    return strtotime($a['date']) - strtotime($b['date']);
});

// Filtrer les événements à venir
$evenements_a_venir = array_filter($tous_evenements, function($evt) {
    return strtotime($evt['date']) >= strtotime('today');
});

// Récupérer les 10 prochains événements
$prochains_evenements = array_slice($evenements_a_venir, 0, 10);
?>

<div class="container">
    <h1 class="h3 mb-4 text-gray-800">Mon Planning</h1>
    
    <div class="row mb-4">
        <!-- Carte des prochains événements -->
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Mes prochains événements</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($prochains_evenements)): ?>
                        <p class="text-center">Vous n'avez aucun événement à venir.</p>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($prochains_evenements as $evt): ?>
                                <div class="timeline-item">
                                    <div class="timeline-badge <?= $evt['classe'] ?>">
                                        <i class="fas fa-calendar"></i>
                                    </div>
                                    <div class="timeline-panel">
                                        <div class="timeline-heading">
                                            <h4 class="timeline-title"><?= htmlspecialchars($evt['titre']) ?></h4>
                                            <p>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock"></i> 
                                                    <?= date('d/m/Y à H:i', strtotime($evt['date'])) ?>
                                                </small>
                                            </p>
                                        </div>
                                        
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Carte des actions rapides -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Actions rapides</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="../rdv_medicaux/index.php" class="btn btn-primary btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-calendar-plus"></i>
                            </span>
                            <span class="text">Voir les événements</span>
                        </a>
                        <a href="../rdv_medicaux" class="btn btn-warning btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-user-md"></i>
                            </span>
                            <span class="text">Prendre un RDV médical</span>
                        </a>
                        <a href="../communautes" class="btn btn-success btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-users"></i>
                            </span>
                            <span class="text">Mes communautés</span>
                        </a>
                        <a href="../associations" class="btn btn-info btn-icon-split">
                            <span class="icon text-white-50">
                                <i class="fas fa-hands-helping"></i>
                            </span>
                            <span class="text">Associations</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Calendrier des événements -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Calendrier mensuel</h6>
        </div>
        <div class="card-body">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Style pour la timeline -->
<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline:before {
    content: "";
    position: absolute;
    top: 0;
    left: 50px;
    height: 100%;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-badge {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: #fff;
    text-align: center;
    line-height: 40px;
    position: absolute;
    left: 30px;
    top: 16px;
    z-index: 1;
}

.timeline-panel {
    position: relative;
    margin-left: 100px;
    padding: 15px;
    border-radius: 5px;
    box-shadow: 0 .15rem 1.75rem 0 rgba(58,59,69,.15);
}

.timeline-panel:before {
    content: " ";
    display: inline-block;
    position: absolute;
    border-top: 15px solid transparent;
    border-right: 15px solid #fff;
    border-bottom: 15px solid transparent;
    left: -15px;
    top: 15px;
}
</style>

<!-- Script pour le calendrier -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Données des événements pour le calendrier
    const events = [
        <?php foreach ($tous_evenements as $evt): ?>
        {
            title: '<?= addslashes($evt['titre']) ?>',
            start: '<?= date('Y-m-d H:i', strtotime($evt['date'])) ?>',
            url: '<?= $evt['lien'] ?>',
            classNames: ['<?= $evt['classe'] ?>']
        },
        <?php endforeach; ?>
    ];
    
    // Initialiser le calendrier FullCalendar
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'fr',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: events,
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        }
    });
    
    calendar.render();
});
</script>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/dashboards/includes/footer_dashboard.php'; ?>
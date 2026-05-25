<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les infos du salarié
$stmt = $conn->prepare("SELECT s.*, e.nom as entreprise_nom FROM salaries s 
                       INNER JOIN entreprises e ON s.id_entreprise = e.id_entreprise 
                       WHERE s.id_salarie = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Type d'événement sélectionné
$type = isset($_GET['type']) ? $_GET['type'] : 'tous';

try {
    // Récupérer tous les événements disponibles pour cette entreprise
    $sql = "SELECT e.*, p.nom as prestataire_nom,
            (SELECT COUNT(*) FROM inscriptions_evenements WHERE id_evenement = e.id_evenement) as nb_inscrits,
            CASE WHEN EXISTS (
                SELECT 1 FROM inscriptions_evenements 
                WHERE id_evenement = e.id_evenement 
                AND id_salarie = :user_id
            ) THEN 1 ELSE 0 END as est_inscrit
            FROM evenements e
            LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
            WHERE e.id_entreprise = :id_entreprise 
            AND e.statut = 'programmé'";

    if($type !== 'tous') {
        $sql .= " AND e.type_evenement = :type";
    }

    $sql .= " ORDER BY e.date_debut ASC";
    
    $stmt = $conn->prepare($sql);
    $params = [
        ':user_id' => $_SESSION['user_id'],
        ':id_entreprise' => $user['id_entreprise']
    ];
    
    if($type !== 'tous') {
        $params[':type'] = $type;
    }
    
    $stmt->execute($params);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    $_SESSION['error'] = "Une erreur est survenue lors de la récupération des événements.";
    $evenements = [];
}

include '../includes/header_dashboard.php';
?>

<div class="container py-4">
    <h2>Événements</h2>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="mb-4">
        <a href="?type=tous" class="btn <?= $type === 'tous' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">Tous</a>
        <a href="?type=webinar" class="btn <?= $type === 'webinar' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">Webinars</a>
        <a href="?type=conference" class="btn <?= $type === 'conference' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">Conférences</a>
        <a href="?type=atelier" class="btn <?= $type === 'atelier' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">Ateliers</a>
        <a href="?type=medical" class="btn <?= $type === 'medical' ? 'btn-primary' : 'btn-outline-primary' ?> me-2">Médical</a>
        <a href="?type=sport" class="btn <?= $type === 'sport' ? 'btn-primary' : 'btn-outline-primary' ?>">Sport</a>
    </div>

    <!-- Événements à venir -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Événements à venir</h5>
        </div>
        <div class="card-body">
            <?php if($evenements): ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Événement</th>
                                <th>Type</th>
                                <th>Prestataire</th>
                                <th>Places</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($evenements as $event): ?>
                                <tr>
                                    <td>
                                        <?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?>
                                        <br>
                                        <small class="text-muted">
                                            jusqu'au <?= date('d/m/Y H:i', strtotime($event['date_fin'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($event['titre']) ?></strong>
                                        <?php if($event['description']): ?>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= ucfirst($event['type_evenement']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($event['prestataire_nom']) ?></td>
                                    <td>
                                        <?php if($event['capacite_max']): ?>
                                            <?= $event['nb_inscrits'] ?>/<?= $event['capacite_max'] ?>
                                        <?php else: ?>
                                            Illimité
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($event['est_inscrit']): ?>
                                            <a href="evenements/desinscriptions.php?id=<?= $event['id_evenement'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Êtes-vous sûr de vouloir vous désinscrire ?')">
                                                Se désinscrire
                                            </a>
                                        <?php else: ?>
                                            <?php if(!$event['capacite_max'] || $event['nb_inscrits'] < $event['capacite_max']): ?>
                                                <a href="evenements/inscriptions.php?id=<?= $event['id_evenement'] ?>" 
                                                   class="btn btn-success btn-sm">
                                                    S'inscrire
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Complet</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center text-muted">Aucun événement disponible</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
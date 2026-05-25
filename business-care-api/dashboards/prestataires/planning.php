<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'prestataires') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();


$stmt = $conn->prepare("SELECT e.*, GROUP_CONCAT(s.nom) as participants 
                       FROM evenements e 
                       LEFT JOIN inscriptions_evenements ie ON e.id_evenement = ie.id_evenement 
                       LEFT JOIN salaries s ON ie.id_salarie = s.id_salarie 
                       WHERE e.id_prestataire = ? 
                       GROUP BY e.id_evenement 
                       ORDER BY e.date_debut");
$stmt->execute([$_SESSION['user_id']]);
$evenements = $stmt->fetchAll();

include '../includes/header_dashboard.php';
?>

<div class="container mt-4">
    <h2>Mon Planning</h2>
    
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Participants</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($evenements): ?>
                            <?php foreach($evenements as $event): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i', strtotime($event['date_debut'])) ?></td>
                                    <td><?= htmlspecialchars($event['titre']) ?></td>
                                    <td><?= htmlspecialchars($event['type_evenement']) ?></td>
                                    <td>
                                        <small>
                                            <?= $event['participants'] ? htmlspecialchars($event['participants']) : 'Aucun participant' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $event['statut'] === 'programmé' ? 'primary' : 
                                            ($event['statut'] === 'en_cours' ? 'success' : 
                                            ($event['statut'] === 'terminé' ? 'secondary' : 'danger')) 
                                        ?>">
                                            <?= ucfirst($event['statut']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">Aucun événement planifié</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_dashboard.php'; ?>
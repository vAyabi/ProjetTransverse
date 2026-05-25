<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Vérification de la session
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';

try {
    // Connexion à la base de données
    $db = new Database();
    $conn = $db->getConnection();

    // Récupération des informations du salarié connecté
    $stmt = $conn->prepare("
        SELECT s.*, e.nom as entreprise_nom, e.type_formule 
        FROM salaries s 
        INNER JOIN entreprises e ON s.id_entreprise = e.id_entreprise 
        WHERE s.id_salarie = :user_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $salarie = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$salarie) {
        throw new Exception("Informations du salarié introuvables");
    }

    // Récupération des événements à venir
    $stmt = $conn->prepare("
        SELECT e.*, p.nom as prestataire_nom,
            (SELECT COUNT(*) FROM inscriptions_evenements ie2 
             WHERE ie2.id_evenement = e.id_evenement) as nb_inscrits,
            CASE WHEN ie.id_salarie IS NOT NULL THEN 1 ELSE 0 END as est_inscrit
        FROM evenements e
        LEFT JOIN prestataires p ON e.id_prestataire = p.id_prestataire
        LEFT JOIN inscriptions_evenements ie ON e.id_evenement = ie.id_evenement 
            AND ie.id_salarie = :user_id
        WHERE e.id_entreprise = :entreprise_id
        AND e.statut = 'programmé'
        ORDER BY e.date_debut ASC
        LIMIT 5
    ");

    $stmt->execute([
        'user_id' => $_SESSION['user_id'],
        'entreprise_id' => $salarie['id_entreprise']
    ]);
    $evenements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistiques des participations
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN e.date_fin < NOW() THEN 1 ELSE 0 END) as participes
        FROM inscriptions_evenements ie
        JOIN evenements e ON ie.id_evenement = e.id_evenement
        WHERE ie.id_salarie = :user_id
    ");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch(Exception $e) {
    $_SESSION['error'] = "Une erreur est survenue: " . $e->getMessage();
    $stats = ['total' => 0, 'participes' => 0];
    $evenements = [];
}

// Inclusion du header
include '../includes/header_dashboard.php';
?>

<!-- CSS du chatbot après le header -->
<link rel="stylesheet" href="assets/css/chatbot.css">

<div class="container py-4">
    <?php if(isset($salarie)): ?>
        <h2>Tableau de bord - <?= htmlspecialchars($salarie['nom']) ?></h2>
        <p class="text-muted">Entreprise : <?= htmlspecialchars($salarie['entreprise_nom']) ?></p>
        
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

        <!-- Statistiques -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Événements à venir</h5>
                        <p class="display-4"><?= count($evenements) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Participations totales</h5>
                        <p class="display-4"><?= $stats['total'] ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Événements passés</h5>
                        <p class="display-4"><?= $stats['participes'] ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Événements à venir -->
        <div class="card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Prochains événements</h5>
                <a href="evenements.php" class="btn btn-primary btn-sm">Voir tous les événements</a>
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
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($event['description'], 0, 100)) ?>...
                                                </small>
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
                                                <span class="badge bg-success">Inscrit</span>
                                            <?php else: ?>
                                                <?php if(!$event['capacite_max'] || $event['nb_inscrits'] < $event['capacite_max']): ?>
                                                    <a href="evenements/inscriptions.php?id=<?= $event['id_evenement'] ?>" 
                                                       class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus"></i> S'inscrire
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
                    <p class="text-center text-muted">Aucun événement à venir</p>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <div class="alert alert-danger">
            Impossible de charger les informations du salarié.
        </div>
    <?php endif; ?>
</div>

<!-- HTML du chatbot -->
<div class="chat-button" onclick="toggleChat()">
    <i class="fas fa-robot"></i> Assistant
</div>

<div class="chat-window" id="chatWindow" style="display:none;">
    <div class="chat-header">
        <h5>Assistant Business Care</h5>
        <span id="chatQuota" class="badge bg-light text-dark"></span>
        <button type="button" class="btn-close" onclick="toggleChat()"></button>
    </div>
    <div class="chat-messages" id="chatMessages">
        <div class="message assistant">
            Bonjour ! Je suis l'assistant Business Care. Comment puis-je vous aider ?
        </div>
    </div>
    <div class="chat-input">
        <input type="text" id="userInput" placeholder="Tapez votre message..." class="form-control">
        <button onclick="sendMessage()" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>

<!-- Script du chatbot inline -->
<script>
// Variables globales simples
let chatQuota = { reste: 0, questions_total: 0, formule: '' };

function toggleChat() {
    const chatWindow = document.getElementById('chatWindow');
    const isVisible = chatWindow.style.display === 'flex';
    chatWindow.style.display = isVisible ? 'none' : 'flex';
    
    if (!isVisible) {
        initChatbot();
    }
}

function initChatbot() {
    fetch('/business-care-api/dashboards/salaries/chatbot_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_quota'
    })
    .then(response => response.json())
    .then(data => {
        if (!data.error) {
            chatQuota = data;
            updateQuotaDisplay();
        }
    })
    .catch(error => console.error('Erreur:', error));
}

function updateQuotaDisplay() {
    const quotaElement = document.getElementById('chatQuota');
    if (!quotaElement) return;
    
    if (chatQuota.formule === 'premium') {
        quotaElement.textContent = 'Illimité';
        quotaElement.className = 'badge bg-success';
    } else {
        quotaElement.textContent = chatQuota.reste + '/' + chatQuota.questions_total;
        if (chatQuota.reste <= 0) {
            quotaElement.className = 'badge bg-danger';
        } else if (chatQuota.reste <= 3) {
            quotaElement.className = 'badge bg-warning';
        } else {
            quotaElement.className = 'badge bg-light text-dark';
        }
    }
}

function sendMessage() {
    const input = document.getElementById('userInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Vérifier le quota
    if (chatQuota.reste <= 0 && chatQuota.formule !== 'premium') {
        appendMessage('assistant', 'Vous avez épuisé votre quota de questions pour ce mois-ci.');
        return;
    }
    
    appendMessage('user', message);
    input.value = '';
    
    // Message de chargement
    const loadingId = 'loading-' + Date.now();
    appendMessage('assistant', '...', loadingId);
    
    fetch('/business-care-api/dashboards/salaries/chatbot_api.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=ask_question&question=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        // Supprimer le message de chargement
        const loadingMsg = document.getElementById(loadingId);
        if (loadingMsg) loadingMsg.remove();
        
        if (data.response) {
            appendMessage('assistant', data.response);
        } else {
            appendMessage('assistant', 'Erreur: ' + (data.error || 'Réponse invalide'));
        }
        
        // Mettre à jour le quota
        if (data.quota) {
            chatQuota = data.quota;
            updateQuotaDisplay();
        }
    })
    .catch(error => {
        const loadingMsg = document.getElementById(loadingId);
        if (loadingMsg) loadingMsg.remove();
        appendMessage('assistant', 'Erreur de connexion');
    });
}

function appendMessage(type, content, id = null) {
    const messages = document.getElementById('chatMessages');
    const div = document.createElement('div');
    div.className = 'message ' + type;
    div.textContent = content;
    if (id) div.id = id;
    messages.appendChild(div);
    messages.scrollTop = messages.scrollHeight;
}

// Event listener pour Enter
document.addEventListener('DOMContentLoaded', function() {
    const userInput = document.getElementById('userInput');
    if (userInput) {
        userInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }
});
</script>

<?php include '../includes/footer_dashboard.php'; ?>
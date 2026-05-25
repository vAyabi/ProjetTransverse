<?php

session_start();

// Vérifie si l'utilisateur est connecté en tant qu'entreprise
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../config/Database.php';
require_once '../../config/stripe.php';
require_once '../../vendor/autoload.php';

// Récupérer les infos de l'entreprise connectée
$id_entreprise = $_SESSION['user_id'];

// Traitement du retour de Stripe
if(isset($_GET['status']) && isset($_GET['devis_id'])) {
    $status = $_GET['status'];
    $devis_id = $_GET['devis_id'];
    
    $db = new Database();
    $conn = $db->getConnection();
    
    if($status === 'success') {
        
        $stmt = $conn->prepare("UPDATE devis SET statut = 'accepté' WHERE id_devis = ? AND id_entreprise = ?");
        if($stmt->execute([$devis_id, $id_entreprise])) {
            $_SESSION['success'] = "Paiement réussi ! Votre devis a été accepté. Vous pouvez maintenant souscrire à un contrat.";
        }
    } else {
        $_SESSION['error'] = "Le paiement a été annulé.";
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Vérifier s'il n'y a pas déjà un contrat actif
        $stmt = $conn->prepare("SELECT COUNT(*) FROM contrats WHERE id_entreprise = ? AND statut = 'actif'");
        $stmt->execute([$id_entreprise]);
        if($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Vous avez déjà un contrat actif. Veuillez le résilier avant d'en créer un nouveau.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
        
        
        $type_formule = $_POST['type_formule'];
        $nb_salaries = (int)$_POST['nb_salaries'];
        $type_paiement = $_POST['type_paiement'];
        
        
        $tarifs_annuels = [
            'starter' => 180,
            'basic' => 150,
            'premium' => 100
        ];
        
        // Calculer le montant
        $montant_annuel_par_salarie = $tarifs_annuels[$type_formule];
        $montant_annuel_total = $montant_annuel_par_salarie * $nb_salaries;
        
        // Ajuster selon le type de paiement
        if($type_paiement === 'mensuel') {
            $montant_total = $montant_annuel_total / 12;
            $description_paiement = "Paiement mensuel";
        } else {
            $montant_total = $montant_annuel_total * 0.9; // Réduction de 10% pour paiement annuel
            $description_paiement = "Paiement annuel (10% de réduction)";
        }
        
        // Créer le devis
        $details = json_encode([
            'formule' => $type_formule,
            'nb_salaries' => $nb_salaries,
            'type_paiement' => $type_paiement,
            'montant_annuel_par_salarie' => $montant_annuel_par_salarie,
            'montant_total' => $montant_total
        ]);
        
        $stmt = $conn->prepare("
            INSERT INTO devis (id_entreprise, montant_total, validite_jours, statut, details) 
            VALUES (?, ?, 30, 'en_attente', ?)
        ");
        
        if($stmt->execute([$id_entreprise, $montant_total, $details])) {
            $devis_id = $conn->lastInsertId();
            
            // Créer la session Stripe
            \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
            
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            
            $checkout_session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Business Care - Formule ' . ucfirst($type_formule),
                            'description' => $nb_salaries . ' salariés - ' . $description_paiement,
                        ],
                        'unit_amount' => round($montant_total * 100), // En centimes
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $protocol . $_SERVER['HTTP_HOST'] . '/business-care-api/dashboards/entreprises/contrats/success.php?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?status=cancel&devis_id=' . $devis_id,
                'metadata' => [
                    'devis_id' => $devis_id,
                    'entreprise_id' => $id_entreprise
                ]
            ]);
            
            // Sauvegarder l'ID de session Stripe
            $stmt = $conn->prepare("UPDATE devis SET stripe_session_id = ? WHERE id_devis = ?");
            $stmt->execute([$checkout_session->id, $devis_id]);
            
            // Rediriger vers Stripe
            header('Location: ' . $checkout_session->url);
            exit();
        }
        
    } catch(Exception $e) {
        $_SESSION['error'] = "Erreur: " . $e->getMessage();
    }
}

// Récupérer l'entreprise et ses infos
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT * FROM entreprises WHERE id_entreprise = ?");
    $stmt->execute([$id_entreprise]);
    $entreprise = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer les devis existants
    $stmt = $conn->prepare("SELECT * FROM devis WHERE id_entreprise = ? ORDER BY created_at DESC");
    $stmt->execute([$id_entreprise]);
    $devis_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}

include_once '../includes/header_dashboard.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Demande de devis</h2>
        <a href="index.php" class="btn btn-secondary">Retour</a>
    </div>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="row">
       
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Nouveau devis</h4>
                </div>
                <div class="card-body">
                    <form method="post" id="devisForm">
                        <div class="mb-4">
                            <h5>Informations de l'entreprise</h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Nom:</strong> <?= htmlspecialchars($entreprise['nom']) ?></p>
                                    <p><strong>Email:</strong> <?= htmlspecialchars($entreprise['email']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="nb_salaries" class="form-label">Nombre de salariés</label>
                                        <input type="number" class="form-control" id="nb_salaries" 
                                               name="nb_salaries" min="1" required value="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Formule souhaitée</h5>
                            <select name="type_formule" id="type_formule" class="form-select" required>
                                <option value="">Choisir une formule</option>
                                <option value="starter">Starter (jusqu'à 30 employés) - 180€/an/salarié</option>
                                <option value="basic">Basic (jusqu'à 250 employés) - 150€/an/salarié</option>
                                <option value="premium">Premium (à partir de 251 employés) - 100€/an/salarié</option>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <h5>Type de paiement</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type_paiement" 
                                       id="paiement_mensuel" value="mensuel" required>
                                <label class="form-check-label" for="paiement_mensuel">
                                    Paiement mensuel
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="type_paiement" 
                                       id="paiement_annuel" value="annuel" required checked>
                                <label class="form-check-label" for="paiement_annuel">
                                    Paiement annuel (10% de réduction)
                                </label>
                            </div>
                        </div>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i>Procéder au paiement
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        
        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 20px;">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Récapitulatif</h5>
                </div>
                <div class="card-body">
                    <div id="recap-devis">
                        <p><strong>Formule:</strong> <span id="recap-formule">-</span></p>
                        <p><strong>Nombre de salariés:</strong> <span id="recap-salaries">-</span></p>
                        <p><strong>Type de paiement:</strong> <span id="recap-paiement">-</span></p>
                        <hr>
                        <h5>Total: <span id="recap-total">-</span></h5>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>
    
    
    <?php if(!empty($devis_list)): ?>
    <div class="card mt-5">
        <div class="card-header">
            <h5 class="mb-0">Historique des devis</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Date</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($devis_list as $devis): ?>
                            <tr>
                                <td>#<?= $devis['id_devis'] ?></td>
                                <td><?= date('d/m/Y', strtotime($devis['created_at'])) ?></td>
                                <td><?= number_format($devis['montant_total'], 2, ',', ' ') ?> €</td>
                                <td>
                                    <span class="badge bg-<?= 
                                        $devis['statut'] === 'accepté' ? 'success' : 
                                        ($devis['statut'] === 'refusé' ? 'danger' : 
                                        ($devis['statut'] === 'expiré' ? 'secondary' : 'warning')) 
                                    ?>">
                                        <?= ucfirst($devis['statut']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($devis['statut'] === 'en_attente'): ?>
                                        <a href="#" class="btn btn-sm btn-primary">
                                            <i class="fas fa-credit-card"></i> Payer
                                        </a>
                                    <?php endif; ?>
                                    <a href="factures/download.php?id=<?= $devis['id_devis'] ?>" 
                                       class="btn btn-sm btn-secondary">
                                        <i class="fas fa-download"></i> PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formuleSelect = document.getElementById('type_formule');
    const nbSalariesInput = document.getElementById('nb_salaries');
    const paiementInputs = document.querySelectorAll('input[name="type_paiement"]');
    
    const tarifs = {
        'starter': 180,
        'basic': 150,
        'premium': 100
    };
    
    function updateRecapitulatif() {
        const formule = formuleSelect.value;
        const nbSalaries = parseInt(nbSalariesInput.value) || 1;
        const typePaiement = document.querySelector('input[name="type_paiement"]:checked');
        
        if(formule && typePaiement) {
            const tarifAnnuel = tarifs[formule];
            const montantAnnuelTotal = tarifAnnuel * nbSalaries;
            let montantFinal;
            
            if(typePaiement.value === 'mensuel') {
                montantFinal = montantAnnuelTotal / 12;
                document.getElementById('recap-paiement').textContent = 'Mensuel';
            } else {
                montantFinal = montantAnnuelTotal * 0.9; // 10% de réduction
                document.getElementById('recap-paiement').textContent = 'Annuel (10% de réduction)';
            }
            
            document.getElementById('recap-formule').textContent = formule.charAt(0).toUpperCase() + formule.slice(1);
            document.getElementById('recap-salaries').textContent = nbSalaries;
            document.getElementById('recap-total').textContent = montantFinal.toFixed(2) + '€';
            
            if(typePaiement.value === 'mensuel') {
                document.getElementById('recap-total').textContent += '/mois';
            } else {
                document.getElementById('recap-total').textContent += '/an';
            }
        }
    }
    
    
    formuleSelect.addEventListener('change', updateRecapitulatif);
    nbSalariesInput.addEventListener('input', updateRecapitulatif);
    paiementInputs.forEach(input => {
        input.addEventListener('change', updateRecapitulatif);
    });
    
    
    updateRecapitulatif();
});
</script>

<?php include_once '../includes/footer_dashboard.php'; ?>
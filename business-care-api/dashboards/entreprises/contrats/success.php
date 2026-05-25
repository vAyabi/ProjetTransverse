<?php

session_start();
require_once '../../../config/Database.php';
require_once '../../../config/stripe.php';
require_once '../../../vendor/autoload.php';


error_log("Success.php appelé avec session_id: " . ($_GET['session_id'] ?? 'NONE'));

if(!isset($_GET['session_id'])) {
    error_log("Pas de session_id dans l'URL");
    header('Location: ../contrat.php');
    exit();
}

try {
    \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
    
    // Récupérer la session Stripe
    $session = \Stripe\Checkout\Session::retrieve($_GET['session_id']);
    
    if($session->payment_status === 'paid') {
        $db = new Database();
        $conn = $db->getConnection();
        $metadata = $session->metadata;
        
        // Commencer une transaction
        $conn->beginTransaction();
        
        try {
            // Si c'est un paiement de devis
            if(isset($metadata->devis_id)) {
                $devis_id = $metadata->devis_id;
                $entreprise_id = $metadata->entreprise_id;
                
                
                $stmt = $conn->prepare("UPDATE devis SET statut = 'accepté' WHERE id_devis = ?");
                $stmt->execute([$devis_id]);
                
                
                $stmt = $conn->prepare("SELECT * FROM devis WHERE id_devis = ?");
                $stmt->execute([$devis_id]);
                $devis = $stmt->fetch();
                
                $details = json_decode($devis['details'], true);
                
                
                $date_debut = date('Y-m-d');
                $date_fin = date('Y-m-d', strtotime('+1 year'));
                
                $stmt = $conn->prepare("
                    INSERT INTO contrats (id_entreprise, date_debut, date_fin, montant_total, type_paiement, statut)
                    VALUES (?, ?, ?, ?, ?, 'actif')
                ");
                $stmt->execute([
                    $entreprise_id, 
                    $date_debut, 
                    $date_fin, 
                    $devis['montant_total'], 
                    $details['type_paiement']
                ]);
                $contrat_id = $conn->lastInsertId();
                
                
                $stmt = $conn->prepare("UPDATE entreprises SET type_formule = ? WHERE id_entreprise = ?");
                $stmt->execute([$details['formule'], $entreprise_id]);
                
                
                $date_echeance = date('Y-m-d');
                if($details['type_paiement'] === 'mensuel') {
                    $date_echeance = date('Y-m-d', strtotime('+1 month'));
                }
                
                $stmt = $conn->prepare("
                    INSERT INTO factures (id_entreprise, id_contrat, montant_total, date_echeance, statut)
                    VALUES (?, ?, ?, ?, 'payée')
                ");
                $stmt->execute([
                    $entreprise_id, 
                    $contrat_id, 
                    $devis['montant_total'], 
                    $date_echeance
                ]);
                
                
                $limites = [
                    'starter' => ['rdv' => 1, 'chatbot' => 6],
                    'basic' => ['rdv' => 2, 'chatbot' => 20],
                    'premium' => ['rdv' => 3, 'chatbot' => 999999] // illimité
                ];
                
                $limite_formule = $limites[$details['formule']];
                $mois = date('n');
                $annee = date('Y');
                
                // Récupérer les salariés
                $stmt = $conn->prepare("SELECT id_salarie FROM salaries WHERE id_entreprise = ? AND statut = 1");
                $stmt->execute([$entreprise_id]);
                $salaries = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach($salaries as $id_salarie) {
                    // Quota RDV
                    $stmt = $conn->prepare("
                        INSERT INTO quota_rdv_medicaux (id_salarie, mois, annee, quota_disponible, quota_total)
                        VALUES (?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE quota_disponible = VALUES(quota_disponible), quota_total = VALUES(quota_total)
                    ");
                    $stmt->execute([$id_salarie, $mois, $annee, $limite_formule['rdv'], $limite_formule['rdv']]);
                    
                    // Quota chatbot
                    $stmt = $conn->prepare("
                        INSERT INTO quota_chatbot (id_salarie, mois, annee, questions_posees, questions_total)
                        VALUES (?, ?, ?, 0, ?)
                        ON DUPLICATE KEY UPDATE questions_total = VALUES(questions_total)
                    ");
                    $stmt->execute([$id_salarie, $mois, $annee, $limite_formule['chatbot']]);
                }
                
                $_SESSION['success'] = "Paiement réussi ! Votre contrat " . ucfirst($details['formule']) . " est maintenant actif.";
            }
            
            // Valider la transaction
            $conn->commit();
            
        } catch(Exception $e) {
            $conn->rollBack();
            throw $e;
        }
    } else {
        $_SESSION['error'] = "Le paiement n'a pas été complété.";
    }
    
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
}


header('Location: ../contrat.php');
exit();
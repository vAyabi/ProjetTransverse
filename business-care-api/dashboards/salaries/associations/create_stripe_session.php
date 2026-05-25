<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
    header('Location: /business-care-api/login.php');
    exit();
}

require_once '../../../config/Database.php';
require_once '../../../config/stripe.php';

// Vérifier les paramètres
if(!isset($_GET['amount']) || !isset($_GET['id_association'])) {
    $_SESSION['error'] = "Informations manquantes";
    header('Location: index.php');
    exit();
}

$amount = floatval($_GET['amount']);
$id_association = $_GET['id_association'];

// Valider le montant
if($amount < 0.50) {
    $_SESSION['error'] = "Le montant minimum est de 0.50€";
    header('Location: index.php');
    exit();
}

try {
    // Récupérer le nom de l'association pour le libellé
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT nom FROM associations WHERE id_association = ?");
    $stmt->execute([$id_association]);
    $association = $stmt->fetch();
    
    if(!$association) {
        throw new Exception("Association non trouvée");
    }
    
    $donation_description = 'Don à ' . $association['nom'];
    
    // Créer la session de paiement Stripe
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'eur',
                'product_data' => [
                    'name' => $donation_description,
                ],
                'unit_amount' => intval($amount * 100),
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/business-care-api/dashboards/salaries/associations/success.php?id_association=' . $id_association,
        'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/business-care-api/dashboards/salaries/associations/index.php',
    ]);

    // Rediriger vers Stripe
    header('Location: ' . $session->url);
    exit();
} catch(Exception $e) {
    $_SESSION['error'] = "Erreur: " . $e->getMessage();
    header('Location: index.php');
    exit();
}
<?php
session_start();
require_once '../../../config/Database.php';
require_once '../../../config/stripe.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        $tarifs = [
            'starter' => ['mensuel' => 180, 'annuel' => 1800],
            'basic' => ['mensuel' => 150, 'annuel' => 1500],
            'premium' => ['mensuel' => 100, 'annuel' => 1000]
        ];

        $montant = $tarifs[$_POST['type_formule']][$_POST['type_paiement']];
        
        // Créer la session Stripe
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $montant * 100,
                    'product_data' => [
                        'name' => 'Contrat Business Care - ' . ucfirst($_POST['type_formule']),
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/business-care-api/dashboards/entreprises/contrats/success.php?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/business-care-api/dashboards/entreprises/contrat.php',
            'metadata' => [
                'type_formule' => $_POST['type_formule'],
                'type_paiement' => $_POST['type_paiement'],
                'id_entreprise' => $_SESSION['user_id']
            ]
        ]);

        header('Location: ' . $session->url);
        exit();

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header('Location: ../contrat.php');
        exit();
    }
}

header('Location: ../contrat.php');
exit();
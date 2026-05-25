<?php
// dashboards/entreprises/contrats/download.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    require_once __DIR__ . '/../../../config/Database.php';
    require_once __DIR__ . '/../../../libs/fpdf/fpdf.php';

    if(!isset($_GET['id'])) {
        header('Location: list.php');
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer le contrat avec les informations de l'entreprise
    $stmt = $conn->prepare("
        SELECT c.*, e.nom as nom_entreprise, e.adresse, e.siret, e.telephone, e.email, e.type_formule
        FROM contrats c
        INNER JOIN entreprises e ON c.id_entreprise = e.id_entreprise
        WHERE c.id_contrat = ? AND c.id_entreprise = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $contrat = $stmt->fetch();

    if(!$contrat) {
        throw new Exception('Contrat non trouvé');
    }

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial','B',20);
            $this->Cell(0,10,utf8_decode('Business Care'),0,1,'C');
            $this->SetFont('Arial','',12);
            $this->Cell(0,10,utf8_decode('110 rue de Rivoli, 75001 Paris'),0,1,'C');
            $this->Cell(0,10,'Tel: 01 23 45 67 89 - Email: contact@businesscare.fr',0,1,'C');
            $this->Ln(20);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,utf8_decode('Business Care - SIRET: XXX XXX XXX - TVA: FR XX XXX XXX XXX - Page '.$this->PageNo()),0,0,'C');
        }
    }

    // Créer le PDF
    $pdf = new PDF();
    $pdf->AddPage();

    // Titre du contrat
    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,utf8_decode('CONTRAT DE SERVICES'),0,1,'C');
    $pdf->Ln(10);

    // Informations du contrat
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(95,10,utf8_decode('Contrat N° ' . str_pad($contrat['id_contrat'], 6, '0', STR_PAD_LEFT)),0,0);
    $pdf->Cell(95,10,'Date: ' . date('d/m/Y'),0,1,'R');
    $pdf->Ln(10);

    // Parties du contrat
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'ENTRE LES PARTIES',0,1);
    $pdf->Ln(5);

    // Business Care
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'1. Le Prestataire:',0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,'Business Care',0,1);
    $pdf->Cell(0,6,'110 rue de Rivoli, 75001 Paris',0,1);
    $pdf->Cell(0,6,'SIRET: XXX XXX XXX',0,1);
    $pdf->Ln(10);

    // Client
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,10,'2. Le Client:',0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,utf8_decode($contrat['nom_entreprise']),0,1);
    if($contrat['adresse']) $pdf->MultiCell(0,6,utf8_decode($contrat['adresse']));
    if($contrat['siret']) $pdf->Cell(0,6,'SIRET: ' . $contrat['siret'],0,1);
    $pdf->Cell(0,6,'Email: ' . $contrat['email'],0,1);
    if($contrat['telephone']) $pdf->Cell(0,6,utf8_decode('Téléphone: ' . $contrat['telephone']),0,1);
    $pdf->Ln(10);

    // Objet du contrat
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'OBJET DU CONTRAT',0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->MultiCell(0,6,utf8_decode("Le présent contrat a pour objet la fourniture de services de bien-être et de santé en entreprise par Business Care au profit du Client, selon la formule " . strtoupper($contrat['type_formule']) . "."));
    $pdf->Ln(10);

    // Durée du contrat
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,utf8_decode('DURÉE DU CONTRAT'),0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,utf8_decode('Date de début: ' . date('d/m/Y', strtotime($contrat['date_debut']))),0,1);
    $pdf->Cell(0,6,utf8_decode('Date de fin: ' . date('d/m/Y', strtotime($contrat['date_fin']))),0,1);
    $pdf->Ln(10);

    // Services inclus
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'SERVICES INCLUS',0,1);
    $pdf->SetFont('Arial','',11);
    
    $services = [
        'starter' => [
            '- 2 activités avec prestataires Business Care',
            '- 1 RDV médical par salarié (présentiel/visio)',
            '- 6 questions au chatbot par salarié',
            '- Accès aux fiches pratiques BC',
            '- Événements et communautés illimités'
        ],
        'basic' => [
            '- 3 activités avec prestataires Business Care',
            '- 2 RDV médicaux par salarié (présentiel/visio)',
            '- 20 questions au chatbot par salarié',
            '- Accès aux fiches pratiques BC',
            '- Conseils hebdomadaires (non personnalisés)',
            '- Événements et communautés illimités'
        ],
        'premium' => [
            '- 4 activités avec prestataires Business Care',
            '- 3 RDV médicaux par salarié (présentiel/visio)',
            '- Chatbot illimité',
            '- Accès aux fiches pratiques BC',
            '- Conseils hebdomadaires personnalisés',
            '- Événements et communautés illimités'
        ]
    ];
    
    foreach($services[$contrat['type_formule']] as $service) {
        $pdf->Cell(0,6,utf8_decode($service),0,1);
    }
    $pdf->Ln(10);

    // Conditions financières
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,utf8_decode('CONDITIONS FINANCIÈRES'),0,1);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(0,6,'Montant: ' . number_format($contrat['montant_total'], 2, ',', ' ') . ' EUR',0,1);
    $pdf->Cell(0,6,utf8_decode('Type de paiement: ' . ucfirst($contrat['type_paiement'])),0,1);
    if($contrat['type_paiement'] === 'mensuel') {
        $pdf->Cell(0,6,utf8_decode('Échéance: Le montant sera prélevé mensuellement'),0,1);
    } else {
        $pdf->Cell(0,6,utf8_decode('Échéance: Paiement annuel avec 10% de réduction'),0,1);
    }
    $pdf->Ln(10);

    // Statut
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'STATUT DU CONTRAT',0,1);
    $pdf->SetFont('Arial','',11);
    $statut_text = [
        'actif' => 'Le contrat est actuellement actif',
        'résilié' => 'Le contrat a été résilié',
        'terminé' => 'Le contrat est arrivé à terme'
    ];
    $pdf->Cell(0,6,utf8_decode($statut_text[$contrat['statut']] ?? 'Statut: ' . $contrat['statut']),0,1);
    $pdf->Ln(20);

    // Signatures
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(95,10,'Pour Business Care',0,0,'C');
    $pdf->Cell(95,10,'Pour le Client',0,1,'C');
    $pdf->Ln(20);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(95,6,'_________________________',0,0,'C');
    $pdf->Cell(95,6,'_________________________',0,1,'C');
    $pdf->Cell(95,6,'Signature et cachet',0,0,'C');
    $pdf->Cell(95,6,'Signature et cachet',0,1,'C');

    // Générer le PDF
    $pdf->Output('D', 'Contrat_BC_' . str_pad($contrat['id_contrat'], 6, '0', STR_PAD_LEFT) . '.pdf');

} catch(Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la génération du contrat.";
    header('Location: list.php');
    exit();
}
<?php
// dashboards/entreprises/factures/download.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    require_once __DIR__ . '/../../../config/Database.php';
    require_once __DIR__ . '/../../../libs/fpdf/fpdf.php';

    if(!isset($_GET['id'])) {
        header('Location: ../contrat.php');
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer la facture avec toutes les informations nécessaires
    $stmt = $conn->prepare("
        SELECT f.*, c.type_paiement, e.nom as nom_entreprise, e.adresse, e.siret, e.telephone, e.email, e.type_formule
        FROM factures f
        INNER JOIN contrats c ON f.id_contrat = c.id_contrat 
        INNER JOIN entreprises e ON f.id_entreprise = e.id_entreprise
        WHERE f.id_facture = ? AND f.id_entreprise = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $facture = $stmt->fetch();

    if(!$facture) {
        throw new Exception('Facture non trouvée');
    }

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial','B',20);
            $this->Cell(0,10,utf8_decode('Business Care'),0,1,'C');
            $this->SetFont('Arial','',12);
            $this->Cell(0,10,utf8_decode('110 rue de Rivoli, 75001 Paris'),0,1,'C');
            $this->Cell(0,10,'Tel: 01 23 45 67 89 - Email: facturation@businesscare.fr',0,1,'C');
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

    // Titre de la facture
    $pdf->SetFont('Arial','B',20);
    $pdf->Cell(0,10,'FACTURE',0,1,'C');
    $pdf->Ln(10);

    // Informations de la facture
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(95,10,utf8_decode('Facture N° ' . str_pad($facture['id_facture'], 6, '0', STR_PAD_LEFT)),0,0);
    $pdf->Cell(95,10,'Date: ' . date('d/m/Y', strtotime($facture['date_echeance'])),0,1,'R');
    $pdf->Ln(10);

    // Facturé à
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,utf8_decode('Facturé à:'),0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,8,utf8_decode($facture['nom_entreprise']),0,1);
    if($facture['siret']) $pdf->Cell(0,6,'SIRET: ' . $facture['siret'],0,1);
    if($facture['adresse']) $pdf->MultiCell(0,6,utf8_decode($facture['adresse']));
    $pdf->Cell(0,6,'Email: ' . $facture['email'],0,1);
    if($facture['telephone']) $pdf->Cell(0,6,utf8_decode('Téléphone: ' . $facture['telephone']),0,1);
    $pdf->Ln(10);

    // Détails de la prestation
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(240,240,240);
    $pdf->Cell(110,10,'Description',1,0,'L',true);
    $pdf->Cell(30,10,'Prix HT',1,0,'C',true);
    $pdf->Cell(30,10,'TVA',1,0,'C',true);
    $pdf->Cell(30,10,'Prix TTC',1,0,'C',true);
    $pdf->Ln();

    $pdf->SetFont('Arial','',11);
    
    // Calcul des montants
    $montant_ht = $facture['montant_total'] / 1.2; // Prix HT
    $montant_tva = $facture['montant_total'] - $montant_ht; // TVA 20%
    
    // Description de la facture
    $description = "Abonnement Business Care - Formule " . ucfirst($facture['type_formule']) . "\n";
    $description .= "Type de paiement: " . ucfirst($facture['type_paiement']);
    
    // Période de facturation
    if($facture['type_paiement'] === 'mensuel') {
        $periode = date('F Y', strtotime($facture['date_echeance']));
        $description .= "\nPériode: " . $periode;
    } else {
        $annee = date('Y', strtotime($facture['date_echeance']));
        $description .= "\nAnnée: " . $annee;
    }
    
    $pdf->MultiCell(110,8,utf8_decode($description),1);
    $y = $pdf->GetY();
    $pdf->SetXY(120, $y-24);
    $pdf->Cell(30,24,number_format($montant_ht, 2, ',', ' ') . ' EUR',1,0,'C');
    $pdf->Cell(30,24,'20%',1,0,'C');
    $pdf->Cell(30,24,number_format($facture['montant_total'], 2, ',', ' ') . ' EUR',1,0,'C');
    $pdf->Ln();

    // Totaux
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(140,8,'Total HT:',0,0,'R');
    $pdf->Cell(50,8,number_format($montant_ht, 2, ',', ' ') . ' EUR',0,1,'R');
    $pdf->Cell(140,8,'TVA (20%):',0,0,'R');
    $pdf->Cell(50,8,number_format($montant_tva, 2, ',', ' ') . ' EUR',0,1,'R');
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(140,10,'Total TTC:',0,0,'R');
    $pdf->Cell(50,10,number_format($facture['montant_total'], 2, ',', ' ') . ' EUR',0,1,'R');

    // Statut de paiement
    $pdf->Ln(15);
    $pdf->SetFont('Arial','B',16);
    if($facture['statut'] === 'payée') {
        $pdf->SetTextColor(0,150,0);
        $pdf->Cell(0,10,utf8_decode('PAYÉE'),0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,8,utf8_decode('Cette facture a été réglée.'),0,1,'C');
    } elseif($facture['statut'] === 'retard') {
        $pdf->SetTextColor(255,0,0);
        $pdf->Cell(0,10,'EN RETARD',0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,8,utf8_decode('Cette facture est en retard de paiement.'),0,1,'C');
    } else {
        $pdf->SetTextColor(255,165,0);
        $pdf->Cell(0,10,'EN ATTENTE',0,1,'C');
        $pdf->SetFont('Arial','',12);
        $pdf->Cell(0,8,utf8_decode('Cette facture est en attente de paiement.'),0,1,'C');
    }
    $pdf->SetTextColor(0,0,0);

    // Informations de paiement
    $pdf->Ln(15);
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(0,8,'Informations de paiement:',0,1);
    $pdf->SetFont('Arial','',10);
    $pdf->Cell(0,6,'IBAN: FR76 3000 6000 0112 3456 7890 189',0,1);
    $pdf->Cell(0,6,'BIC: AGRIFRPP',0,1);
    $pdf->Cell(0,6,utf8_decode('Référence à rappeler: FAC-' . str_pad($facture['id_facture'], 6, '0', STR_PAD_LEFT)),0,1);

    // Mentions légales
    $pdf->Ln(20);
    $pdf->SetFont('Arial','I',9);
    $pdf->MultiCell(0,5,utf8_decode("Conformément à la loi, le montant de la pénalité pour retard de paiement est calculé sur la base du taux d'intérêt légal multiplié par 3.\nEn cas de retard de paiement, une indemnité forfaitaire de 40 euros sera due."));

    // Conditions générales
    $pdf->Ln(10);
    $pdf->SetFont('Arial','I',8);
    $pdf->MultiCell(0,4,utf8_decode("Business Care SARL au capital de 50 000 euros - RCS Paris XXX XXX XXX\nN° TVA Intracommunautaire: FR XX XXX XXX XXX\nSiège social: 110 rue de Rivoli, 75001 Paris"));

    // Générer le PDF
    $pdf->Output('D', 'Facture_BC_' . str_pad($facture['id_facture'], 6, '0', STR_PAD_LEFT) . '.pdf');

} catch(Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la génération de la facture.";
    header('Location: ../contrat.php');
    exit();
}
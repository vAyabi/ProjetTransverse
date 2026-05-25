<?php
// dashboards/entreprises/devis/download.php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'entreprises') {
    header('Location: /business-care-api/login.php');
    exit();
}

try {
    require_once __DIR__ . '/../../../config/Database.php';
    require_once __DIR__ . '/../../../libs/fpdf/fpdf.php';

    if(!isset($_GET['id'])) {
        header('Location: ../demande_devis.php');
        exit();
    }

    $db = new Database();
    $conn = $db->getConnection();

    // Récupérer le devis
    $stmt = $conn->prepare("
        SELECT d.*, e.nom as nom_entreprise, e.adresse, e.siret, e.telephone, e.email
        FROM devis d
        INNER JOIN entreprises e ON d.id_entreprise = e.id_entreprise
        WHERE d.id_devis = ? AND d.id_entreprise = ?
    ");
    $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
    $devis = $stmt->fetch();

    if(!$devis) {
        throw new Exception('Devis non trouvé');
    }

    // Décoder les détails du devis
    $details = json_decode($devis['details'], true);

    class PDF extends FPDF {
        function Header() {
            $this->SetFont('Arial','B',20);
            $this->Cell(80);
            $this->Cell(30,10,'Business Care',0,1,'C');
            $this->SetFont('Arial','',12);
            $this->Cell(80);
            $this->Cell(30,10,'110 rue de Rivoli, 75001 Paris',0,1,'C');
            $this->Ln(20);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('Arial','I',8);
            $this->Cell(0,10,'Business Care - SIRET: XXX XXX XXX - TVA: FR XX XXX XXX XXX',0,0,'C');
        }
    }

    // Créer le PDF
    $pdf = new PDF();
    $pdf->AddPage();

    // Titre du devis
    $pdf->SetFont('Arial','B',18);
    $pdf->Cell(0,10,utf8_decode('DEVIS'),0,1,'C');
    $pdf->Ln(10);

    // Informations du devis
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(95,10,utf8_decode('Devis N° ' . str_pad($devis['id_devis'], 6, '0', STR_PAD_LEFT)),0,0);
    $pdf->Cell(95,10,'Date: ' . date('d/m/Y', strtotime($devis['created_at'])),0,1,'R');
    $pdf->Cell(95,10,utf8_decode('Validité: ' . $devis['validite_jours'] . ' jours'),0,1);
    
    $date_validite = date('d/m/Y', strtotime($devis['created_at'] . ' + ' . $devis['validite_jours'] . ' days'));
    $pdf->Cell(95,10,'Date limite: ' . $date_validite,0,1);
    $pdf->Ln(10);

    // Informations client
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(0,10,'Client',0,1);
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(0,10,utf8_decode($devis['nom_entreprise']),0,1);
    if($devis['siret']) $pdf->Cell(0,10,'SIRET: ' . $devis['siret'],0,1);
    if($devis['adresse']) $pdf->MultiCell(0,10,utf8_decode($devis['adresse']));
    $pdf->Cell(0,10,'Email: ' . $devis['email'],0,1);
    if($devis['telephone']) $pdf->Cell(0,10,utf8_decode('Téléphone: ' . $devis['telephone']),0,1);
    $pdf->Ln(10);

    // Tableau des services
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(130,10,'Description',1);
    $pdf->Cell(30,10,'Prix unitaire',1);
    $pdf->Cell(30,10,'Total',1);
    $pdf->Ln();

    $pdf->SetFont('Arial','',12);
    
    // Détails de la formule
    $formule_nom = ucfirst($details['formule']);
    $nb_salaries = $details['nb_salaries'];
    $prix_unitaire = $details['montant_annuel_par_salarie'] ?? 0;
    $type_paiement = ucfirst($details['type_paiement']);
    
    $description = "Formule $formule_nom - $nb_salaries salarié(s)\n";
    $description .= "Paiement: $type_paiement";
    if($type_paiement === 'Annuel') {
        $description .= " (10% de réduction)";
    }
    
    $pdf->MultiCell(130,10,$description,1);
    $pdf->SetXY(140, $pdf->GetY()-20);
    $pdf->Cell(30,20,number_format($prix_unitaire, 2, ',', ' ') . ' €',1,0,'C');
    $pdf->SetXY(170, $pdf->GetY());
    $pdf->Cell(30,20,number_format($devis['montant_total'], 2, ',', ' ') . ' €',1,1,'C');
    
    // Total
    $pdf->Ln(10);
    $pdf->SetFont('Arial','B',14);
    $pdf->Cell(160,10,'Total HT:',0,0,'R');
    $pdf->Cell(30,10,number_format($devis['montant_total']/1.2, 2, ',', ' ') . ' €',0);
    $pdf->Ln();
    $pdf->Cell(160,10,'TVA (20%):',0,0,'R');
    $pdf->Cell(30,10,number_format($devis['montant_total']*0.2/1.2, 2, ',', ' ') . ' €',0);
    $pdf->Ln();
    $pdf->Cell(160,10,'Total TTC:',0,0,'R');
    $pdf->Cell(30,10,number_format($devis['montant_total'], 2, ',', ' ') . ' €',0);
    
    // Statut du devis
    $pdf->Ln(20);
    $pdf->SetFont('Arial','B',14);
    $statut_couleur = [
        'en_attente' => [255, 165, 0],
        'accepté' => [0, 150, 0],
        'refusé' => [255, 0, 0],
        'expiré' => [128, 128, 128]
    ];
    
    $couleur = $statut_couleur[$devis['statut']] ?? [0, 0, 0];
    $pdf->SetTextColor($couleur[0], $couleur[1], $couleur[2]);
    $pdf->Cell(0,10,'Statut: ' . strtoupper($devis['statut']),0,1,'C');
    $pdf->SetTextColor(0,0,0);
    
    // Conditions
    $pdf->Ln(10);
    $pdf->SetFont('Arial','',10);
    $pdf->MultiCell(0,5,"Conditions:\n- Ce devis est valable " . $devis['validite_jours'] . " jours\n- Les prix sont indiqués en euros TTC\n- Le contrat prendra effet après validation du paiement");

    // Générer le PDF
    $pdf->Output('D', 'Devis_BC_' . str_pad($devis['id_devis'], 6, '0', STR_PAD_LEFT) . '.pdf');

} catch(Exception $e) {
    error_log($e->getMessage());
    $_SESSION['error'] = "Une erreur est survenue lors de la génération du devis.";
    header('Location: ../demande_devis.php');
    exit();
}
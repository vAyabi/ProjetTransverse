
<?php
session_start();
if(!isset($_SESSION['logged_in']) || $_SESSION['user_type'] !== 'salaries') {
   header('Location: /business-care-api/login.php');
   exit();
}

require_once '../../../config/Database.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer la catégorie sélectionnée
$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : 'tous';

// Récupérer les conseils
$sql = "SELECT * FROM conseils";
if($categorie !== 'tous') {
   $sql .= " WHERE categorie = :categorie";
}
$sql .= " ORDER BY date_creation DESC";

$stmt = $conn->prepare($sql);
if($categorie !== 'tous') {
   $stmt->bindParam(':categorie', $categorie);
}
$stmt->execute();
$conseils = $stmt->fetchAll();

include '../../includes/header_dashboard.php';
?>

<div class="container py-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
       <h2>Espace Conseils</h2>
   </div>

   <!-- Filtres par catégorie -->
   <div class="btn-group mb-4">
       <a href="?categorie=tous" class="btn <?= $categorie === 'tous' ? 'btn-primary' : 'btn-outline-primary' ?>">Tous</a>
       <a href="?categorie=sante" class="btn <?= $categorie === 'sante' ? 'btn-primary' : 'btn-outline-primary' ?>">Santé</a>
       <a href="?categorie=bien_etre" class="btn <?= $categorie === 'bien_etre' ? 'btn-primary' : 'btn-outline-primary' ?>">Bien-être</a>
       <a href="?categorie=travail" class="btn <?= $categorie === 'travail' ? 'btn-primary' : 'btn-outline-primary' ?>">Travail</a>
   </div>

   <!-- Liste des conseils -->
   <div class="row">
       <?php foreach($conseils as $conseil): ?>
           <div class="col-md-4 mb-4">
               <div class="card h-100">
                   <div class="card-body">
                       <span class="badge bg-info mb-2">
                           <?= ucfirst(str_replace('_', ' ', $conseil['categorie'])) ?>
                       </span>
                       <h5 class="card-title"><?= htmlspecialchars($conseil['titre']) ?></h5>
                       <p class="card-text text-muted">
                           <?= substr(htmlspecialchars($conseil['contenu']), 0, 150) ?>...
                       </p>
                       <a href="view.php?id=<?= $conseil['id_conseil'] ?>" class="btn btn-outline-primary">
                           Lire la suite
                       </a>
                   </div>
                   <div class="card-footer text-muted">
                       Publié le <?= date('d/m/Y', strtotime($conseil['date_creation'])) ?>
                   </div>
               </div>
           </div>
       <?php endforeach; ?>
       
       <?php if(empty($conseils)): ?>
           <div class="col-12">
               <p class="text-center text-muted">Aucun conseil disponible pour le moment.</p>
           </div>
       <?php endif; ?>
   </div>
</div>

<?php include '../../includes/footer_dashboard.php'; ?>
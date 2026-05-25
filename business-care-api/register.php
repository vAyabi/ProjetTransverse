<?php 
session_start();
include 'includes/header.php'; 
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Inscription</h3>
                    
                    <?php 
                    if(isset($_SESSION['success'])) {
                        echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
                        unset($_SESSION['success']);
                    }
                    
                    if(isset($_SESSION['error'])) {
                        echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                        unset($_SESSION['error']);
                    }
                    ?>

                    <div class="mb-4">
                        <select class="form-select" id="typeCompte" onchange="toggleForm()">
                            <option value="" selected disabled>Sélectionnez votre type de compte</option>
                            <option value="entreprises">Entreprise</option>
                            <option value="prestataires">Prestataire</option>
                        </select>
                    </div>

                    <div id="entrepriseForm" style="display: none;">
                        <form action="register_process.php" method="POST">
                            <input type="hidden" name="type" value="entreprises">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="nom" placeholder="Nom de l'entreprise" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="siret" placeholder="SIRET" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email professionnel" required>
                            </div>
                            <div class="mb-3">
                                <input type="tel" class="form-control" name="telephone" placeholder="Téléphone" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="adresse" placeholder="Adresse" required>
                            </div>
                            <div class="mb-3">
                                <select class="form-select" name="type_formule" required>
                                    <option value="starter">Starter</option>
                                    <option value="basic">Basic</option>
                                    <option value="premium">Premium</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Mot de passe" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">S'inscrire en tant qu'entreprise</button>
                        </form>
                    </div>

                    <div id="prestataireForm" style="display: none;">
                        <form action="register_process.php" method="POST">
                            <input type="hidden" name="type" value="prestataires">
                            <div class="mb-3">
                                <input type="text" class="form-control" name="nom" placeholder="Nom complet" required>
                            </div>
                            <div class="mb-3">
                                <select class="form-select" name="type_prestation" required>
                                    <option value="medical">Médical</option>
                                    <option value="bien-etre">Bien-être</option>
                                    <option value="sport">Sport</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <input type="email" class="form-control" name="email" placeholder="Email professionnel" required>
                            </div>
                            <div class="mb-3">
                                <input type="tel" class="form-control" name="telephone" placeholder="Téléphone" required>
                            </div>
                            <div class="mb-3">
                                <input type="text" class="form-control" name="rib" placeholder="RIB" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password" placeholder="Mot de passe" required>
                            </div>
                            <div class="mb-3">
                                <input type="password" class="form-control" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">S'inscrire en tant que prestataire</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-3">
                <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleForm() {
    const type = document.getElementById('typeCompte').value;
    document.getElementById('entrepriseForm').style.display = type === 'entreprises' ? 'block' : 'none';
    document.getElementById('prestataireForm').style.display = type === 'prestataires' ? 'block' : 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
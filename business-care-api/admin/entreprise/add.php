<?php
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/config/Database.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/models/Entreprise.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        $entreprise = new Entreprise($db);
        
        // Vérifier si l'email existe déjà
        $entreprise->email = $_POST['email'];
        if ($entreprise->emailExists()) {
            throw new Exception("Une entreprise avec cet email existe déjà.");
        }
        
        // Assigner les valeurs
        $entreprise->nom = $_POST['nom'];
        $entreprise->siret = $_POST['siret'];
        $entreprise->password = $_POST['password']; // Mot de passe fourni par l'admin
        $entreprise->telephone = $_POST['telephone'] ?? null;
        $entreprise->adresse = $_POST['adresse'] ?? null;
        $entreprise->type_formule = $_POST['type_formule'];
        $entreprise->statut = 1;
        
        // Créer l'entreprise
        if ($entreprise->create()) {
            $_SESSION['success'] = "Entreprise créée avec succès!";
            header('Location: index.php');
            exit();
        } else {
            throw new Exception("Impossible de créer l'entreprise");
        }
        
    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/header.php';
?>

<div class="container-fluid px-4 py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-building me-2"></i>Ajouter une entreprise</h4>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form action="" method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom de l'entreprise <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom" name="nom" required>
                                <div class="invalid-feedback">
                                    Le nom de l'entreprise est requis.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="siret" class="form-label">SIRET <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="siret" name="siret" required 
                                       pattern="[0-9]{14}" maxlength="14"
                                       title="Le SIRET doit contenir 14 chiffres">
                                <div class="invalid-feedback">
                                    Le SIRET doit contenir exactement 14 chiffres.
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                <div class="invalid-feedback">
                                    Veuillez entrer une adresse email valide.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Le mot de passe doit contenir au moins 6 caractères.
                                </div>
                                <small class="text-muted">Ce mot de passe sera communiqué à l'entreprise.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="type_formule" class="form-label">Type de formule <span class="text-danger">*</span></label>
                                <select class="form-select" id="type_formule" name="type_formule" required>
                                    <option value="">-- Sélectionner --</option>
                                    <option value="starter">Starter</option>
                                    <option value="basic">Basic</option>
                                    <option value="premium">Premium</option>
                                </select>
                                <div class="invalid-feedback">
                                    Veuillez sélectionner un type de formule.
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" id="adresse" name="adresse" rows="3"></textarea>
                        </div>

                        <p class="text-muted mb-3"><span class="text-danger">*</span> Champs obligatoires</p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Créer l'entreprise
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation Bootstrap
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                
                form.classList.add('was-validated')
            }, false)
        })
})()

// Formater le SIRET pendant la saisie
document.getElementById('siret').addEventListener('input', function (e) {
    this.value = this.value.replace(/\D/g, '');
});

// Fonction pour afficher/masquer le mot de passe
function togglePassword() {
    var passwordInput = document.getElementById('password');
    var toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php include $_SERVER['DOCUMENT_ROOT'] . '/business-care-api/admin/includes/footer.php'; ?>
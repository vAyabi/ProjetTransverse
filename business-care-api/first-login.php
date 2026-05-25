<?php
session_start();
if(!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_user_email'])) {
    header('Location: login.php');
    exit();
}

require_once 'config/Database.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new Database();
        $conn = $db->getConnection();

        // vérification que les mots de passe correspondent
        if($_POST['new_password'] !== $_POST['confirm_password']) {
            throw new Exception("Les mots de passe ne correspondent pas");
        }

        // hash du nouveau mot de passe
        $salt = 'IF7EFECFGC%SDH';
        $password_salt = $_POST['new_password'] . $salt;
        $hashed_password = hash('sha256', $password_salt);

        // mise à jour du mot de passe et activation du compte
        $stmt = $conn->prepare("
            UPDATE salaries 
            SET password = ?, first_login = 0, statut = 1 
            WHERE id_salarie = ?
        ");
        
        if($stmt->execute([$hashed_password, $_SESSION['temp_user_id']])) {
            // configuration de la session
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['user_type'] = 'salaries';
            $_SESSION['user_email'] = $_SESSION['temp_user_email'];

            // nettoyage des variables temporaires
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_user_email']);
            unset($_SESSION['temp_user_type']);

            header('Location: dashboards/salaries/index.php');
            exit();
        }
        
        throw new Exception("Erreur lors de la mise à jour du mot de passe");

    } catch(Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}

include 'includes/header.php';
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">Première connexion</h3>
                    
                    <?php if(isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?= $_SESSION['error'] ?>
                            <?php unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-center mb-4">
                        Veuillez choisir votre nouveau mot de passe pour activer votre compte.
                    </p>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Activer mon compte</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
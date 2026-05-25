<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm mx-auto" style="max-width: 400px;">
        <div class="card-body p-4">
            <h3 class="text-center mb-4">Portail Administration</h3>
            <?php 
            if(isset($_SESSION['error'])) {
                echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
                unset($_SESSION['error']);
            }
            ?>
            <form action="admin_login.php" method="POST">
                <div class="mb-3">
                    <input type="email" 
                           name="email" 
                           class="form-control" 
                           placeholder="Email" 
                           value="<?= isset($_COOKIE['admin_email']) ? htmlspecialchars($_COOKIE['admin_email']) : ''; ?>" 
                           required>
                </div>
                <div class="mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Mot de passe" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Connexion</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
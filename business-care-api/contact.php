<?php include 'includes/header.php'; ?>

<div class="container mt-5">
    <h1 class="text-center mb-5">Nous contacter</h1>
    
    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="contact_process.php" method="POST">
                <div class="mb-3">
                    <select class="form-select" name="type" required>
                        <option value="" disabled selected>Je suis...</option>
                        <option value="entreprise">Une entreprise</option>
                        <option value="salarie">Un salarié</option>
                        <option value="prestataire">Un prestataire</option>
                    </select>
                </div>
                <div class="mb-3">
                    <input type="email" class="form-control" name="email" placeholder="Votre email" required>
                </div>
                <div class="mb-3">
                    <textarea class="form-control" name="message" rows="5" placeholder="Votre message" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary w-100">Envoyer</button>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
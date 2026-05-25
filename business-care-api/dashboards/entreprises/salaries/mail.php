<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php';

function sendInvitationMail($to, $nom, $email, $password) {
    $mail = new PHPMailer(true);

    try {
        
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        $mail->Username = 'votre-email@gmail.com';
        $mail->Password = 'votre-mot-de-passe';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        // Destinataires
        $mail->setFrom('noreply@businesscare.fr', 'Business Care');
        $mail->addAddress($to);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = 'Invitation Business Care';
        $mail->Body = "
            <h2>Bienvenue sur Business Care</h2>
            <p>Bonjour " . htmlspecialchars($nom) . ",</p>
            <p>Vous avez été invité à rejoindre Business Care.</p>
            <p>Vos identifiants de connexion :</p>
            <ul>
                <li><strong>Email :</strong> {$email}</li>
                <li><strong>Mot de passe :</strong> {$password}</li>
            </ul>
            <p>Pour vous connecter, <a href='http://localhost/business_care/'>cliquez ici</a></p>
            <p>Pensez à changer votre mot de passe à la première connexion.</p>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
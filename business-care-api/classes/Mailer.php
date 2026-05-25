<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class Mailer {
    private $mail;

    public function __construct() {
        require_once __DIR__ . '/../vendor/autoload.php';
        
        $this->mail = new PHPMailer(true);
        $this->mail->SMTPDebug = SMTP::DEBUG_OFF;
        $this->mail->isSMTP();
        $this->mail->Host = MAIL_HOST;
        $this->mail->SMTPAuth = true;
        $this->mail->Username = MAIL_USERNAME;
        $this->mail->Password = MAIL_PASSWORD;
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = MAIL_PORT;
        $this->mail->CharSet = 'UTF-8';

        $this->mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    }

    public function sendInvitation($email, $nom, $code) {
        try {
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Activation de votre compte Business Care';
            
            $body = "
                <h2>Bienvenue sur Business Care</h2>
                <p>Bonjour " . htmlspecialchars($nom) . ",</p>
                <p>Votre compte a été créé sur la plateforme Business Care.</p>
                <p><strong>Votre code d'activation est : " . $code . "</strong></p>
                <p>Lors de votre première connexion, vous devrez :</p>
                <ol>
                    <li>Saisir votre email</li>
                    <li>Entrer ce code d'activation</li>
                    <li>Créer votre nouveau mot de passe</li>
                </ol>
                <p>Ce code n'est valable qu'une seule fois.</p>
                <p>Pour vous connecter, <a href='http://localhost/business_care/auth/login.php'>cliquez ici</a></p>
            ";
            
            $this->mail->Body = $body;
            $this->mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Erreur d'envoi de mail : " . $e->getMessage());
            return false;
        }
    }
}
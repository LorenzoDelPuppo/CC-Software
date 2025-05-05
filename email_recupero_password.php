<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';  // Assicurati che il percorso sia corretto

function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Configurazione SMTP
        $mail->isSMTP();                                             // Setto il protocollo SMTP
        $mail->Host       = 'smtp.gmail.com';                          // Imposto il server SMTP
        $mail->SMTPAuth   = true;                                      // Attivo l'autenticazione SMTP
        $mail->Username   = 'noreplychecapelli@gmail.com';             // Inserisci la tua email
        $mail->Password   = 'jiyk rpbo uzcg cdee';                     // Inserisci la tua password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;               // Imposto la crittografia
        $mail->Port       = 465;                                       // Imposto la porta

        // Imposto l'email di partenza
        $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');
        $mail->addAddress($toEmail); // Destinatario
        $mail->addReplyTo('tommaso.poletto@iisvittorioveneto.it', 'Supporto Che Capelli'); // Risposta

        // Corpo dell'email
        $mail->isHTML(true);
        $mail->Subject = 'Recupero Password - Che Capelli';
        $mail->Body = "
            <p>Ciao,</p>
            <p>Abbiamo ricevuto una richiesta di recupero della password per il tuo account. Se non sei stato tu a richiederlo, ignora questa email.</p>
            <p>Per reimpostare la tua password, clicca sul link sottostante:</p>
            <p><a href='$resetLink' style='background-color:#28a745;color:#ffffff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Reimposta Password</a></p>
            <p>Grazie,<br>Team Che Capelli</p>
        ";

        // Corpo alternativo dell'email in caso di problemi con l'HTML
        $mail->AltBody = "Ciao,\n\n"
                       . "Abbiamo ricevuto una richiesta di recupero della password per il tuo account. Se non sei stato tu, ignora questa email.\n\n"
                       . "Per reimpostare la tua password, visita questo link:\n"
                       . "$resetLink\n\n"
                       . "Grazie,\n"
                       . "Team Che Capelli";

        // Invio dell'email
        $mail->send();
        echo 'Email inviata con successo!';
    } catch (Exception $e) {
        echo "Errore nell'invio dell'email. Mailer Error: {$mail->ErrorInfo}";
    }
}
?>

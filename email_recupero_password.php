<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();                                             // Setto il protocollo SMTP
    $mail->Host       = 'smtp.gmail.com';                          // Imposto il server SMTP
    $mail->SMTPAuth   = true;                                      // Attivo l'autenticazione SMTP
    $mail->Username   = 'noreplychecapelli@gmail.com';             // Inserisci la tua email
    $mail->Password   = 'jiyk rpbo uzcg cdee';                     // Inserisci la tua password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;               // Imposto la crittografia
    $mail->Port       = 465;                                       // Imposto la porta

    // Imposto l'email di partenza
    $mail->setFrom('noreplychecapelli@gmail.com', 'Mailer');
    $mail->addAddress('tommaso.poletto@iisvittorioveneto.it', 'Joe User');     
    $mail->addReplyTo('tommaso.poletto@iisvittorioveneto.it', 'Information');

    // Corpo dell'email
    $mail->isHTML(true);
    $mail->Subject = 'Cambio Password - Che Capelli';
    $mail->Body = "
        <p>Ciao <b>{nome utente}</b>,</p>
        <p>Abbiamo ricevuto una richiesta di recupero della password per il tuo account. Se non sei stato tu a richiederlo, ignora questa email.</p>
        <p>Per reimpostare la tua password, clicca sul link sottostante:</p>
        <p><a href='http://{tuo_dominio}/cambio_password_email.php' style='background-color:#28a745;color:#ffffff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Reimposta Password</a></p>
        <p>Grazie,<br>Team Che Capelli</p>
    ";

    // Corpo alternativo dell'email in caso di problemi con l'HTML
    $mail->AltBody = "Ciao {Nome utente},\n\n"
                   . "Abbiamo ricevuto una richiesta di recupero della password per il tuo account. Se non sei stato tu, ignora questa email.\n\n"
                   . "Per reimpostare la tua password, visita questo link:\n"
                   . "{LINK}\n\n"
                   . "Grazie,\n"
                   . "Team Che Capelli";

    $mail->send();
    echo 'Message has been sent';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
?>

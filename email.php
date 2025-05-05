<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();                                            
    $mail->Host       = 'smtp.gmail.com';                     
    $mail->SMTPAuth   = true;                                  
    $mail->Username   = 'noreplychecapelli@gmail.com';                   
    $mail->Password   = 'jiyk rpbo uzcg cdee';                              
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           
    $mail->Port       = 465;                                   

    $mail->setFrom('noreplychecapelli@gmail.com', 'Mailer');
    $mail->addAddress('tommaso.poletto@iisvittorioveneto.it', 'tommaso');     
    $mail->addReplyTo('tommaso.poletto@iisvittorioveneto.it', 'Information');

 
    $mail->isHTML(true);                                  
    $mail->Subject = 'Cambio Password - Che Capelli';
    $mail->Body    = "
        <p>Ciao <b>{nome utente}</b>,</p>
        <p>Abbiamo ricevuto una richiesta di recupero della password per il tuo account. Se non sei stato tu a richiederlo, ignora questa email.</p>
        <p>Per reimpostare la tua password, clicca sul link sottostante:</p>
        <p><a href='{LINK}' style='background-color:#28a745;color:#ffffff;padding:10px 15px;text-decoration:none;border-radius:5px;'>Reimposta Password</a></p>
        <p>Grazie,<br>Team Che Capelli</p>
    ";

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
<?php
require_once 'config/mail_sender.php';

$recipientEmail = 'utente@example.com';
$recipientName  = 'Nome Utente';
$subject        = 'Reset Password';
$bodyHTML       = '<p>Ciao, clicca su questo link per reimpostare la tua password: <a href="https://tuosito.com/reset">Reset</a></p>';
$bodyPlain      = 'Ciao, visita https://tuosito.com/reset per reimpostare la tua password.';

if(sendEmail($recipientEmail, $recipientName, $subject, $bodyHTML, $bodyPlain)) {
    echo "Email inviata con successo.";
} else {
    echo "Errore nell'invio dell'email.";
}
?>

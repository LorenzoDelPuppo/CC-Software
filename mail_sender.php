<?php
// mail_sender.php

// Includi l'autoload di Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Includi il file di configurazione con le costanti (config_mail.php)
require_once 'config_mail.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\OAuth;
use League\OAuth2\Client\Provider\Google;

/**
 * Invia un'email utilizzando Gmail tramite OAuth2.
 *
 * @param string $recipientEmail L'indirizzo email del destinatario
 * @param string $recipientName Il nome del destinatario
 * @param string $subject Oggetto dell'email
 * @param string $bodyHTML Corpo dell'email in HTML
 * @param string $bodyPlain Corpo dell'email in testo semplice (opzionale)
 *
 * @return bool True se l'email è stata inviata, false altrimenti
 */
function sendEmail($recipientEmail, $recipientName, $subject, $bodyHTML, $bodyPlain = '')
{
    $mail = new PHPMailer(true);
    try {
        // Configurazione SMTP per Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->Port       = 587;
        $mail->SMTPSecure = 'tls';
        $mail->SMTPAuth   = true;

        // Configurazione OAuth2
        $mail->AuthType = 'XOAUTH2';
        $provider = new Google([
            'clientId'     => GMAIL_CLIENT_ID,
            'clientSecret' => GMAIL_CLIENT_SECRET,
        ]);
        $mail->setOAuth(
            new OAuth([
                'provider'       => $provider,
                'clientId'       => GMAIL_CLIENT_ID,
                'clientSecret'   => GMAIL_CLIENT_SECRET,
                'refreshToken'   => GMAIL_REFRESH_TOKEN,
                'userName'       => GMAIL_SENDER_EMAIL,
            ])
        );

        // Imposta mittente e destinatario
        $mail->setFrom(GMAIL_SENDER_EMAIL, GMAIL_SENDER_NAME);
        $mail->addAddress($recipientEmail, $recipientName);

        // Imposta l'oggetto e il contenuto dell'email
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body    = $bodyHTML;
        $mail->AltBody = $bodyPlain;

        // Invia l'email
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Errore nell\'invio dell\'email: ' . $mail->ErrorInfo);
        return false;
    }
}
?>

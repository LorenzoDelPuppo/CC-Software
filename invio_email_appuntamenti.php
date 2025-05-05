<?php
// Abilitazione degli errori per il debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Includi la libreria PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Assicurati che il percorso sia corretto per il tuo autoload di Composer

// Connessione al database
require_once 'connect.php';
  // Assicurati che il percorso sia corretto

// Calcola la data tra 3 giorni
$date = new DateTime();
$date->modify('+3 days');
$future_date = $date->format('Y-m-d H:i:s');  // Formato 'YYYY-MM-DD HH:MM:SS'

// Ottieni tutti gli appuntamenti tra oggi e 3 giorni dal giorno corrente
$query = "SELECT a.dateTime, c.email, c.fName, c.lName, s.nameS 
          FROM appointment a
          JOIN Customer c ON a.customer_id = c.customer_id
          JOIN servicesOfAppointment soa ON soa.appointment_id = a.appointment_id
          JOIN serviceCC s ON soa.service_id = s.service_id
          WHERE a.dateTime BETWEEN NOW() AND ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $future_date);
$stmt->execute();
$result = $stmt->get_result();

// Configura PHPMailer
$mail = new PHPMailer(true);

try {
    // Imposta i parametri di invio
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com'; // Impostazioni del server SMTP di Gmail
    $mail->SMTPAuth = true;
    $mail->Username = 'noreplychecapelli@gmail.com';  // La tua email
    $mail->Password = 'jiyk rpbo uzcg cdee';  // La tua password (da usare con attenzione)
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;

    // Imposta il mittente
    $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');

    // Per ogni appuntamento, invia un'email
    while ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $fName = $row['fName'];
        $lName = $row['lName'];
        $appointmentDate = $row['dateTime'];
        $serviceName = $row['nameS'];

        // Imposta il destinatario dell'email
        $mail->addAddress($email, "$fName $lName");

        // Contenuto dell'email
        $mail->isHTML(true);
        $mail->Subject = 'Promemoria Appuntamento - Che Capelli';
        $mail->Body = "
            <p>Ciao <b>$fName $lName</b>,</p>
            <p>Questo è un promemoria per il tuo appuntamento con Che Capelli, che è programmato tra 3 giorni, il <b>$appointmentDate</b>.</p>
            <p>Servizio: <b>$serviceName</b></p>
            <p>Ti aspettiamo!</p>
            <p>Grazie,<br>Team Che Capelli</p>
        ";

        // Invia l'email
        $mail->send();

        // Reset dell'indirizzo per l'invio della prossima email
        $mail->clearAddresses();
    }

    echo 'Tutte le email sono state inviate con successo!';

} catch (Exception $e) {
    echo "Si è verificato un errore nell'invio delle email. Mailer Error: {$mail->ErrorInfo}";
}

// Chiudi la connessione
$stmt->close();
$conn->close();
?>

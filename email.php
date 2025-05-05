<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Assicurati che PHPMailer sia installato via Composer

// Connessione al database
$servername = "localhost";
$username = "root";  // Cambia se necessario
$password = "";  // Cambia se necessario
$dbname = "db_cc";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

// Ottieni gli appuntamenti tra 2 giorni
$query = "
    SELECT c.email, c.fName, a.dateTime
    FROM appointment a
    JOIN Customer c ON a.customer_id = c.customer_id
    WHERE DATE(a.dateTime) = DATE_ADD(CURDATE(), INTERVAL 2 DAY) 
    AND c.wants_notification = 1
";

$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $email = $row['email'];
        $nome = $row['fName'];
        $dataAppuntamento = date("d/m/Y H:i", strtotime($row['dateTime']));

        $mail = new PHPMailer(true);
        
        try {
            // Configura il server SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreplychecapelli@gmail.com';  // Inserisci il tuo username
            $mail->Password   = 'jiyk rpbo uzcg cdee';  // Inserisci la tua password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;

            // Imposta il mittente e il destinatario
            $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');
            $mail->addAddress($email, $nome);
            $mail->addReplyTo('noreplychecapelli@gmail.com', 'No Reply');

            // Corpo del messaggio
            $mail->isHTML(true);
            $mail->Subject = 'Promemoria Appuntamento';
            $mail->Body    = "
                <p>Ciao <b>$nome</b>,</p>
                <p>Ti ricordiamo che hai un appuntamento il <b>$dataAppuntamento</b>.</p>
                <p>Se non puoi partecipare, ti preghiamo di contattarci.</p>
                <p>Grazie,<br>Team Che Capelli</p>
            ";

            $mail->AltBody = "Ciao $nome,\n\n"
                           . "Ti ricordiamo che hai un appuntamento il $dataAppuntamento.\n\n"
                           . "Se non puoi partecipare, ti preghiamo di contattarci.\n\n"
                           . "Grazie,\n"
                           . "Team Che Capelli";

            // Invia l'email
            $mail->send();
            echo "Email inviata a $email<br>";
        } catch (Exception $e) {
            // Log dell'errore
            echo "Errore nell'invio dell'email a $email: {$mail->ErrorInfo}<br>";
        }
    }
    echo "Email inviate con successo!";
} else {
    echo "Nessun appuntamento da ricordare.";
}

$conn->close();

?>

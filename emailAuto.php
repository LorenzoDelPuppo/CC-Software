<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'connect.php'; // tua connessione al DB

// Data di 3 giorni da oggi
$targetDate = date('Y-m-d', strtotime('+3 days'));

try {
    $stmt = $conn->prepare("
        SELECT a.appointment_id, a.dateTime, c.fName, c.lName, c.email
        FROM appointment a
        JOIN Customer c ON a.customer_id = c.customer_id
        WHERE DATE(a.dateTime) = :targetDate
        AND c.wants_notification = 1
    ");
    $stmt->execute([':targetDate' => $targetDate]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($appointments as $app) {
        $mail = new PHPMailer(true);

        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                     
        $mail->SMTPAuth   = true;                                  
        $mail->Username   = 'noreplychecapelli@gmail.com';           
        $mail->Password   = 'jiyk rpbo uzcg cdee';                  
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           
        $mail->Port       = 465;                                    

        $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');
        $mail->addAddress($app['email'], $app['fName'] . ' ' . $app['lName']);
        $mail->addReplyTo('noreplychecapelli@gmail.com', 'Che Capelli');

        $date = date('d/m/Y', strtotime($app['dateTime']));
        $time = date('H:i', strtotime($app['dateTime']));

        $mail->isHTML(true);                                  
        $mail->Subject = 'Promemoria Appuntamento - Che Capelli';

        $mail->Body = "
            <p>Ciao <b>{$app['fName']}</b>,</p>
            <p>Ti ricordiamo che hai un appuntamento presso <b>Che Capelli</b> il giorno <b>{$date}</b> alle <b>{$time}</b>.</p>
            <p>Ti aspettiamo!</p>
            <p><i>Questa è un'email automatica, non rispondere a questo messaggio.</i></p>
        ";

        $mail->AltBody = "Ciao {$app['fName']},\n\nHai un appuntamento il {$date} alle {$time}.\n\nTi aspettiamo!\n\n- Che Capelli";

        $mail->send();
        echo "Promemoria inviato a {$app['email']}<br>";
    }

} catch (Exception $e) {
    echo "Errore nell'invio email: {$mail->ErrorInfo}";
} catch (PDOException $e) {
    echo "Errore DB: " . $e->getMessage();
}

<?php
require 'connect.php'; // Collegamento al database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Controllo se l'email esiste
    $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $customer_id = $row['customer_id'];

        // Genera un token sicuro
        $token = bin2hex(random_bytes(32));

        // Salva il token nel database
        $stmt = $conn->prepare("INSERT INTO password_reset (customer_id, token) VALUES (?, ?) ON DUPLICATE KEY UPDATE token = ?");
        $stmt->bind_param("iss", $customer_id, $token, $token);
        $stmt->execute();

        // Link per il reset della password
        $reset_link = "http://tuodominio.com/change_password.php?token=" . $token;

        // Invia l'email
        $to = $email;
        $subject = "Recupero Password";
        $message = "Clicca su questo link per reimpostare la password: $reset_link";
        $headers = "From: noreply@tuodominio.com";

        if (mail($to, $subject, $message, $headers)) {
            echo "Email inviata con successo!";
        } else {
            echo "Errore nell'invio della mail.";
        }
    } else {
        echo "Email non trovata.";
    }
}
?>

<form method="post">
    <input type="email" name="email" placeholder="Inserisci la tua email" required>
    <button type="submit">Invia</button>
</form>

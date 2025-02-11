<?php
session_start();
require 'connect.php'; // Collegamento al database
require 'session.php'; // Include il file session.php

check_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email = $_SESSION['email']; // Usa l'email per identificare l'utente
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $response = ["status" => "error", "message" => ""];

    // Controllo che le nuove password coincidano
    if ($new_password !== $confirm_password) {
        $response["message"] = "Le nuove password non coincidono!";
    } else {
        // Recupero dell'ID utente e della password attuale dal database
        $stmt = $conn->prepare("SELECT customer_id, password FROM Customer WHERE email = ?");
        $stmt->bind_param("s", $user_email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($customer_id, $hashedPassword);
            $stmt->fetch();

            // Verifica della password attuale
            if (password_verify($old_password, $hashedPassword)) {
                // Hash della nuova password
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Aggiornamento della password nel database
                $update_stmt = $conn->prepare("UPDATE Customer SET password = ? WHERE customer_id = ?");
                $update_stmt->bind_param("si", $new_hashed_password, $customer_id);

                if ($update_stmt->execute()) {
                    $response["status"] = "success";
                    $response["message"] = "Password aggiornata con successo!";
                } else {
                    $response["message"] = "Errore durante l'aggiornamento!";
                }
            } else {
                $response["message"] = "La password attuale non è corretta!";
            }
        } else {
            $response["message"] = "Utente non trovato!";
        }

        $stmt->close();
        $conn->close();
    }

    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambia Password</title>
    <script>
        function changePassword(event) {
            event.preventDefault(); // Evita il ricaricamento della pagina

            let formData = new FormData(document.getElementById("passwordForm"));

            fetch("change_password.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let messageBox = document.getElementById("message");
                let retryButton = document.getElementById("retryButton");

                messageBox.innerText = data.message;
                messageBox.style.display = "block";

                if (data.status === "success") {
                    messageBox.style.color = "green";
                    retryButton.style.display = "none"; // Nasconde il pulsante riprova
                    document.getElementById("passwordForm").reset(); // Resetta il form
                } else {
                    messageBox.style.color = "red";
                    retryButton.style.display = "inline-block"; // Mostra il pulsante riprova
                }
            })
            .catch(error => console.error("Errore:", error));
        }

        function retry() {
            document.getElementById("message").style.display = "none"; // Nasconde il messaggio di errore
            document.getElementById("retryButton").style.display = "none"; // Nasconde il pulsante riprova
            document.getElementById("passwordForm").reset(); // Resetta il form
        }
    </script>
</head>
<body>

    <form id="passwordForm" onsubmit="changePassword(event)">
        <label>Password attuale:</label>
        <input type="password" name="old_password" required><br>

        <label>Nuova password:</label>
        <input type="password" name="new_password" required><br>

        <label>Conferma nuova password:</label>
        <input type="password" name="confirm_password" required><br>

        <button type="submit">Cambia Password</button>
        <button type="button" id="retryButton" style="display: none;" onclick="retry()">Riprova</button>
    </form>

    <div id="message" style="display: none; font-weight: bold;"></div>

</body>
</html>

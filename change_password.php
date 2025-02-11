<?php
session_start();
require_once 'connect.php'; // Collegamento al database
require_once 'session.php'; // Include il file session.php

//check_login();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION['email'])) {
        echo json_encode(["status" => "error", "message" => "Sessione non valida, effettua nuovamente l'accesso."]);
        exit;
    }
    
    $user_email = $_SESSION['email']; // Usa l'email per identificare l'utente
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $response = ["status" => "error", "message" => ""];

    if (empty($old_password) || empty($new_password) || empty($confirm_password)) {
        $response["message"] = "Tutti i campi sono obbligatori!";
    } elseif ($new_password !== $confirm_password) {
        $response["message"] = "Le nuove password non coincidono!";
    } else {
        $stmt = $conn->prepare("SELECT customer_id, password FROM Customer WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $user_email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($customer_id, $hashedPassword);
                $stmt->fetch();
                
                if (password_verify($old_password, $hashedPassword)) {
                    $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                    $update_stmt = $conn->prepare("UPDATE Customer SET password = ? WHERE customer_id = ?");
                    if ($update_stmt) {
                        $update_stmt->bind_param("si", $new_hashed_password, $customer_id);
                        if ($update_stmt->execute()) {
                            $response["status"] = "success";
                            $response["message"] = "Password aggiornata con successo!";
                        } else {
                            $response["message"] = "Errore durante l'aggiornamento!";
                        }
                        $update_stmt->close();
                    } else {
                        $response["message"] = "Errore nella preparazione della query di aggiornamento.";
                    }
                } else {
                    $response["message"] = "La password attuale non è corretta!";
                }
            } else {
                $response["message"] = "Utente non trovato!";
            }
            $stmt->close();
        } else {
            $response["message"] = "Errore nella preparazione della query.";
        }
    }

    $conn->close();
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
            event.preventDefault();

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
                messageBox.style.color = (data.status === "success") ? "green" : "red";
                retryButton.style.display = (data.status === "success") ? "none" : "inline-block";
                
                if (data.status === "success") {
                    document.getElementById("passwordForm").reset();
                }
            })
            .catch(error => console.error("Errore:", error));
        }

        function retry() {
            document.getElementById("message").style.display = "none";
            document.getElementById("retryButton").style.display = "none";
            document.getElementById("passwordForm").reset();
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

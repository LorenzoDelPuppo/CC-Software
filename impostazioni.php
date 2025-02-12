<?php
session_start();
require_once 'connect.php'; // Includi qui il file di connessione al database

// Verifica che l'utente sia loggato
if (!isset($_SESSION['email'])) {
    // Se non è loggato, reindirizza alla pagina di login oppure mostra un messaggio di errore
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$message = "";

// Recupera dal database il record del cliente in base all'email
$query = "SELECT customer_id, preference FROM Customer WHERE email = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $customer_id = $row['customer_id'];
        $current_preference = $row['preference'];
    } else {
        die("Errore: Cliente non trovato.");
    }
    $stmt->close();
} else {
    die("Errore nella preparazione della query.");
}

// Se il form viene inviato, aggiorna il campo preference
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_preference = $_POST['preference'] ?? '';
    // Verifica che il valore inviato sia tra quelli consentiti
    $allowed = ['Barbara', 'Giulia', 'Casuale'];
    if (!in_array($new_preference, $allowed)) {
        $message = "Preferenza non valida.";
    } else {
        $updateQuery = "UPDATE Customer SET preference = ? WHERE customer_id = ?";
        if ($stmt = $conn->prepare($updateQuery)) {
            $stmt->bind_param("si", $new_preference, $customer_id);
            if ($stmt->execute()) {
                $message = "Preferenza aggiornata correttamente.";
                $current_preference = $new_preference;
            } else {
                $message = "Errore durante l'aggiornamento: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $message = "Errore nella preparazione della query di aggiornamento.";
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impostazioni Preferenza</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2em;
        }
        form {
            margin-top: 1em;
        }
        label, select {
            font-size: 1.1em;
        }
        input[type="submit"] {
            margin-top: 1em;
            padding: 0.5em 1em;
            font-size: 1em;
        }
        .message {
            padding: 1em;
            background-color: #e0ffe0;
            border: 1px solid #00a000;
            margin-bottom: 1em;
        }
    </style>
</head>
<body>
    <h1>Impostazioni Preferenza</h1>
    <?php if (!empty($message)): ?>
        <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form action="impostazioni.php" method="post">
        <label for="preference">Seleziona la tua preferenza:</label>
        <select name="preference" id="preference">
            <option value="Barbara" <?php echo ($current_preference === 'Barbara') ? 'selected' : ''; ?>>Barbara</option>
            <option value="Giulia" <?php echo ($current_preference === 'Giulia') ? 'selected' : ''; ?>>Giulia</option>
            <option value="Casuale" <?php echo ($current_preference === 'Casuale') ? 'selected' : ''; ?>>Casuale</option>
        </select>
        <br><br>
        <input type="submit" value="Salva Impostazioni">
    </form>
</body>
</html>

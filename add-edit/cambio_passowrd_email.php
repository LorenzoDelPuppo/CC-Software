<?php
session_start();
require_once __DIR__ . '/../connect.php';  // Assicurati che il percorso sia corretto

// Verifica che l'utente sia loggato
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_email = $_SESSION['email'];
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validazione dei campi
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Tutti i campi sono obbligatori!";
    } elseif ($new_password !== $confirm_password) {
        $message = "Le nuove password non coincidono!";
    } else {
        // Recupera il record del cliente
        $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $user_email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($customer_id);
                $stmt->fetch();
                
                // Crea l'hash della nuova password
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Aggiorna la password nel database
                $update_stmt = $conn->prepare("UPDATE Customer SET password = ? WHERE customer_id = ?");
                if ($update_stmt) {
                    $update_stmt->bind_param("si", $new_hashed_password, $customer_id);
                    if ($update_stmt->execute()) {
                        $message = "Password aggiornata con successo!";
                    } else {
                        $message = "Errore durante l'aggiornamento!";
                    }
                    $update_stmt->close();
                } else {
                    $message = "Errore nella preparazione della query di aggiornamento.";
                }
            } else {
                $message = "Utente non trovato!";
            }
            $stmt->close();
        } else {
            $message = "Errore nella preparazione della query.";
        }
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambia Password</title>
  <link rel="stylesheet" href=".././style/style_input.css">
</head>
<body>
  <!-- Contenitore per il logo -->
  <div class="logo-container">
    <a href=".././view-get/menu.php">
      <img src=".././style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>
  <!-- Contenitore principale -->
  <div class="form-container">
    <h2>Cambia Password</h2>
    <?php if (!empty($message)): ?>
      <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>
    <form action="cambio_password_email.php" method="post">
      <label for="new_password">Nuova Password</label>
      <input type="password" id="new_password" name="new_password" required placeholder="Inserisci">
      
      <label for="confirm_password">Conferma Nuova Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required placeholder="Inserisci">
      
      <button type="submit">Cambia Password</button>
    </form>
  </div>
</body>
</html>

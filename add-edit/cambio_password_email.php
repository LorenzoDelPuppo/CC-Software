<?php
session_start();
require_once __DIR__ . '/../connect.php';  // Assicurati che il percorso sia corretto

$message = "";

// Recupera il token dalla query string
$token = $_GET['token'] ?? '';  // Recupera il token passato nell'URL

// Se il token è vuoto, mostriamo un errore
if (empty($token)) {
    $message = "Token non valido!";
} else {
    // Verifica se l'utente è loggato, altrimenti chiediamo di inserire l'email
    if (isset($_SESSION['email'])) {
        // Se l'utente è loggato, recuperiamo l'email dalla sessione
        $email = $_SESSION['email'];
    } else {
        // Se l'utente non è loggato, chiediamo di inserire l'email
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'])) {
            $email = $_POST['email'];  // Recuperiamo l'email dal form
        } else {
            $message = "L'utente non è loggato. Inserisci l'email.";
        }
    }

    // Se l'email è stata trovata (via sessione o tramite il form), procediamo
    if (!empty($email)) {
        // Verifica se il form di reset della password è stato inviato
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password'])) {
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Validazione dei campi
            if (empty($new_password) || empty($confirm_password)) {
                $message = "Tutti i campi sono obbligatori!";
            } elseif ($new_password !== $confirm_password) {
                $message = "Le nuove password non coincidono!";
            } else {
                // Hash della nuova password
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

                // Debugging: Verifica che la password hashata sia corretta
                echo "Nuova password hashata: " . $new_hashed_password . "<br>";

                // Aggiorna la password nel database
                $stmt = $conn->prepare("UPDATE Customer SET password = ? WHERE email = ?");
                if ($stmt) {
                    // Debug: Verifica la query prima di eseguirla
                    echo "Query: UPDATE Customer SET password = ? WHERE email = ?<br>";
                    echo "Email: " . $email . "<br>";

                    $stmt->bind_param("ss", $new_hashed_password, $email);
                    if ($stmt->execute()) {
                        $message = "Password aggiornata con successo!";
                    } else {
                        $message = "Errore durante l'aggiornamento della password!";
                    }
                    $stmt->close();
                } else {
                    $message = "Errore nella preparazione della query.";
                }
            }
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
  <title>Cambia Password</title>
  <link rel="stylesheet" href="../style/style_input.css">
</head>
<body>
  <!-- Contenitore per il logo -->
  <div class="logo-container">
    <a href="../view-get/menu.php">
      <img src="../style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>
  <!-- Contenitore principale -->
  <div class="form-container">
    <h2>Cambia Password</h2>
    <?php if (!empty($message)): ?>
      <div class="message"><?php echo htmlspecialchars($message); ?></div>
    <?php endif; ?>

    <!-- Se l'utente non è loggato, chiedi di inserire l'email -->
    <?php if (empty($email)): ?>
      <form action="cambio_password_email.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" required placeholder="Inserisci la tua email">
        <button type="submit">Invia</button>
      </form>
    <?php else: ?>
      <!-- Modulo di cambio password -->
      <form action="cambio_password_email.php?token=<?php echo htmlspecialchars($token); ?>" method="post">
        <label for="new_password">Nuova Password</label>
        <input type="password" id="new_password" name="new_password" required placeholder="Inserisci">

        <label for="confirm_password">Conferma Nuova Password</label>
        <input type="password" id="confirm_password" name="confirm_password" required placeholder="Inserisci">

        <button type="submit">Cambia Password</button>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>

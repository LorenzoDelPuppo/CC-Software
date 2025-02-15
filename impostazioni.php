<?php
session_start();
require_once 'connect.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$messagePreference = "";

// Gestione dell'aggiornamento della preferenza (azione tradizionale via POST)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action']) && $_POST['action'] === 'update_preference') {
    $new_preference = $_POST['preference'] ?? '';
    $allowed = ['Barbara', 'Giulia', 'Casuale'];
    
    if (!in_array($new_preference, $allowed)) {
        $messagePreference = "Preferenza non valida.";
    } else {
        // Recupera l'ID del cliente
        $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($customer_id);
            if ($stmt->fetch()) {
                $stmt->close();
                // Aggiorna la preferenza
                $updateStmt = $conn->prepare("UPDATE Customer SET preference = ? WHERE customer_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("si", $new_preference, $customer_id);
                    if ($updateStmt->execute()) {
                        $messagePreference = "Preferenza aggiornata correttamente.";
                    } else {
                        $messagePreference = "Errore durante l'aggiornamento: " . $updateStmt->error;
                    }
                    $updateStmt->close();
                } else {
                    $messagePreference = "Errore nella preparazione della query di aggiornamento.";
                }
            } else {
                $messagePreference = "Cliente non trovato.";
            }
        } else {
            $messagePreference = "Errore nella preparazione della query.";
        }
    }
}

// Recupera la preferenza attuale
$stmt = $conn->prepare("SELECT preference FROM Customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($current_preference);
$stmt->fetch();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<script src="menu_profilo.js" defer></script>
<link rel="stylesheet" href="style/barra_alta.css">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Impostazioni</title>
</head>
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href="menu.php">
      <img src="style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href="profilo.php" class="dropdown-item">Profilo</a>
    <a href="impostazioni.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href="logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>

</div>
<body>
  <!-- Contenitore Impostazioni -->
  <div class="settings-container">
    <h1>Impostazioni</h1>
    
    <!-- Sezione per la Preferenza -->
    <section>
      <h2>Preferenza</h2>
      <?php if (!empty($messagePreference)): ?>
        <div class="message"><?php echo htmlspecialchars($messagePreference); ?></div>
      <?php endif; ?>
      <form action="impostazioni.php" method="post">
        <!-- Campo nascosto per identificare l'azione -->
        <input type="hidden" name="action" value="update_preference">
        <label for="preference">Seleziona la tua preferenza:</label>
        <select name="preference" id="preference">
          <option value="Barbara" <?php echo ($current_preference === 'Barbara') ? 'selected' : ''; ?>>Barbara</option>
          <option value="Giulia" <?php echo ($current_preference === 'Giulia') ? 'selected' : ''; ?>>Giulia</option>
          <option value="Casuale" <?php echo ($current_preference === 'Casuale') ? 'selected' : ''; ?>>Casuale</option>
        </select>
        <button type="submit">Salva Impostazioni</button>
      </form>
    </section>

    <!-- Sezione per il Cambio Password -->
    <section>
      <h2>Password</h2>
      <!-- Pulsante che reindirizza alla pagina per il cambio password -->
      <button type="button" onclick="window.location.href='cambia_password.php'">Cambia Password</button>
    </section>

    <!-- Pulsante per tornare al Menu -->
  </div>
</body>
</html>

<?php 
session_start();
require_once __DIR__ . '/../connect.php';

// Verifica che l'utente sia loggato
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
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
<script src=".././js/menu_profilo.js" defer></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href=".././style/style_impostazioni.css">
  <title>Impostazioni</title>
</head>
<?php include '.././view-get/barra.php'; ?>

</div>
<body>
  <!-- Contenitore Impostazioni -->
  <div class="settings-container">
    <h1>Impostazioni</h1>
    
    <!-- Sezione per la Preferenza -->
    <section>
      <h2>Preferenze</h2>
      <div class="switch-container">
    <label for="toggle">Promemoria</label>
      <label class="switch">
        <input type="checkbox" id="toggle">
        <span class="slider"></span>
      </label>
    </div>
      <?php if (!empty($messagePreference)): ?>
        <div class="message"><?php echo htmlspecialchars($messagePreference); ?></div>
      <?php endif; ?>
      <form action=".././add-edit/impostazioni.php" method="post">
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
      <!-- Pulsante che reindirizza alla pagina per il cambio password -->
      <a href=".././add-edit/cambia_password.php" class="change-password">Cambia password</a>
    </section>

    <!-- Pulsante per tornare al Menu -->
  </div>
</body>
</html>

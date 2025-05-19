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

// Gestione dell'aggiornamento delle preferenze
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['action'])) {

    // Aggiorna la preferenza dell'operatrice
    if ($_POST['action'] === 'update_preference') {
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

    // Aggiorna la preferenza delle notifiche
    if ($_POST['action'] === 'update_notifications') {
        $wants_notification = isset($_POST['wants_notification']) ? 1 : 0;

        // Recupera l'ID del cliente
        $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->bind_result($customer_id);
            if ($stmt->fetch()) {
                $stmt->close();
                // Aggiorna le notifiche
                $updateStmt = $conn->prepare("UPDATE Customer SET wants_notification = ? WHERE customer_id = ?");
                if ($updateStmt) {
                    $updateStmt->bind_param("ii", $wants_notification, $customer_id);
                    if ($updateStmt->execute()) {
                        $messagePreference = $wants_notification ? "Notifiche abilitate." : "Notifiche disabilitate.";
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
$stmt = $conn->prepare("SELECT preference, wants_notification FROM Customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($current_preference, $current_notification);
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
<body>

<?php include '.././view-get/barra.php'; ?>

<div class="settings-container">
    <h1>Impostazioni</h1>

    <hr class="settings-divider"> <!-- Linea nera -->

    <!-- Sezione Preferenze -->
    <section>
        <h2>Preferenze</h2>

        <div class="switch-container">
            <label for="toggle">Promemoria</label>
            <label class="switch">
                <input type="checkbox" id="toggle" name="wants_notification" <?php echo ($current_notification == 1) ? 'checked' : ''; ?>>
                <span class="slider"></span>
            </label>
        </div>

        <?php if (!empty($messagePreference)): ?>
            <div class="message"><?php echo htmlspecialchars($messagePreference); ?></div>
        <?php endif; ?>

        <!-- Form per aggiornare le notifiche -->
        <form action=".././add-edit/impostazioni.php" method="post">
            <input type="hidden" name="action" value="update_notifications">
            <button type="submit">Salva Notifiche</button>
        </form>

        <hr class="settings-divider">

        <!-- Sezione operatrice -->
        <h2>Seleziona la tua operatrice preferita</h2>
        <form action=".././add-edit/impostazioni.php" method="post">
            <input type="hidden" name="action" value="update_preference">
            <label for="preference">Seleziona la tua operatrice preferita:</label>
            <select name="preference" id="preference">
                <option value="Barbara" <?php echo ($current_preference === 'Barbara') ? 'selected' : ''; ?>>Barbara</option>
                <option value="Giulia" <?php echo ($current_preference === 'Giulia') ? 'selected' : ''; ?>>Giulia</option>
                <option value="Casuale" <?php echo ($current_preference === 'Casuale') ? 'selected' : ''; ?>>Casuale</option>
            </select>
            <button type="submit">Salva Preferenza</button>
        </form>
    </section>

    <hr class="settings-divider"> <!-- Linea nera -->

    <!-- Sezione Cambio Password -->
    <section>
        <a href=".././add-edit/cambia_password.php" class="change-password">Cambia password</a>
    </section>

</div>

</body>
</html>

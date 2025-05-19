<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['email']) || !isset($_SESSION['customer_id'])) {
    header('Location: ../add-edit/login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Compila tutti i campi.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Le password non coincidono.";
    } else {
        $hashed = password_hash($new_password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("UPDATE Customer SET password = ? WHERE customer_id = ?");
        $stmt->bind_param("si", $hashed, $_SESSION['customer_id']);

        if ($stmt->execute()) {
            $message = "Password aggiornata con successo.";
            // Qui puoi fare logout automatico o redirect a dashboard
        } else {
            $message = "Errore durante l'aggiornamento della password.";
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<title>Cambia Password</title>
<style>
body { font-family: Arial, sans-serif; background: #fafafa; margin: 40px; }
.container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1);}
h2 { text-align: center; }
.message { margin-bottom: 15px; color: red; }
form label { display: block; margin-top: 10px; font-weight: bold; }
form input { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
form button { margin-top: 15px; width: 100%; padding: 10px; background: #28a745; color: white; border: none; cursor: pointer; border-radius: 5px; }
form button:hover { background: #218838; }
</style>
</head>
<body>
<div class="container">
<h2>Cambia Password</h2>

<?php if ($message !== ''): ?>
  <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post" action="reset_password_temp.php">
  <label for="new_password">Nuova Password</label>
  <input type="password" id="new_password" name="new_password" required autofocus />

  <label for="confirm_password">Conferma Nuova Password</label>
  <input type="password" id="confirm_password" name="confirm_password" required />

  <button type="submit">Aggiorna Password</button>
</form>
</div>
</body>
</html>

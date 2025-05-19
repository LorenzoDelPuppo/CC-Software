<?php
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = '';
$step = 1; // step 1: richiesta codice; step 2: inserimento codice+password
$email = '';

// Pulizia codici scaduti
$conn->query("DELETE FROM password_resets WHERE expires_at < NOW()");

function sendCodeEmail($email, $code) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreplychecapelli@gmail.com';
        $mail->Password = 'tua_password_per_app_specifica';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Codice verifica reset password - Che Capelli';
        $mail->Body = "<p>Ciao,</p><p>Il tuo codice per reimpostare la password è: <strong>$code</strong></p><p>Valido per 1 ora.</p>";
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Errore invio email: " . $mail->ErrorInfo;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['code'])) {
        // Step 1: richiedi codice
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Email non valida.";
        } else {
            // Verifica esistenza email
            $stmt = $conn->prepare("SELECT * FROM Customer WHERE email = ?");
            if (!$stmt) die("Errore DB: " . $conn->error);
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $message = "Email non trovata.";
            } else {
                // Genera codice 6 cifre e salva
                $code = random_int(100000, 999999);
                $expires_at = date('Y-m-d H:i:s', time() + 3600);

                $stmt2 = $conn->prepare("REPLACE INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
                if (!$stmt2) die("Errore DB insert: " . $conn->error);
                $stmt2->bind_param("sss", $email, $code, $expires_at);
                $stmt2->execute();
                $stmt2->close();

                // Invio email
                $esito = sendCodeEmail($email, $code);
                if ($esito === true) {
                    $message = "Codice inviato via email. Controlla la tua casella.";
                    $step = 2;
                } else {
                    $message = $esito;
                }
            }
            $stmt->close();
        }
    } elseif (isset($_POST['email'], $_POST['code'], $_POST['new_password'], $_POST['confirm_password'])) {
        // Step 2: verifica codice e reset password
        $email = trim($_POST['email']);
        $code = trim($_POST['code']);
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Email non valida.";
            $step = 2;
        } elseif ($code === '') {
            $message = "Inserisci il codice inviato.";
            $step = 2;
        } elseif ($new_password === '' || $confirm_password === '') {
            $message = "Compila tutti i campi.";
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $message = "Le password non coincidono.";
            $step = 2;
        } else {
            $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()");
            if (!$stmt) die("Errore DB: " . $conn->error);
            $stmt->bind_param("ss", $email, $code);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res->num_rows === 0) {
                $message = "Codice non valido o scaduto.";
                $step = 2;
            } else {
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt2 = $conn->prepare("UPDATE Customer SET password = ? WHERE email = ?");
                if (!$stmt2) die("Errore DB update: " . $conn->error);
                $stmt2->bind_param("ss", $hashed, $email);
                if ($stmt2->execute()) {
                    $stmt2->close();
                    $stmt3 = $conn->prepare("DELETE FROM password_resets WHERE email = ? AND token = ?");
                    if (!$stmt3) die("Errore DB delete: " . $conn->error);
                    $stmt3->bind_param("ss", $email, $code);
                    $stmt3->execute();
                    $stmt3->close();

                    $message = "Password aggiornata con successo. Ora puoi fare login.";
                    $step = 1;
                } else {
                    $message = "Errore aggiornamento password.";
                    $step = 2;
                }
            }
            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Recupero Password con Codice</title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; background: #fafafa; }
.container { max-width: 400px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1);}
h2 { text-align: center; }
.message { margin: 15px 0; padding: 10px; background: #ffdddd; border: 1px solid #dd4444; border-radius: 4px; color: #a00; }
form label { display: block; margin: 15px 0 5px; font-weight: bold; }
form input { width: 100%; padding: 8px; box-sizing: border-box; }
form button { margin-top: 20px; width: 100%; padding: 10px; background: #007bff; color: white; border: none; font-weight: bold; cursor: pointer; border-radius: 5px; }
form button:hover { background: #0056b3; }
</style>
</head>
<body>
<div class="container">
<h2>Recupero Password</h2>
<?php if ($message !== ''): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($step === 1): ?>
<form method="post" action="cambio_password_email.php">
    <label for="email">Inserisci la tua Email</label>
    <input type="email" id="email" name="email" required autofocus>
    <button type="submit">Richiedi Codice</button>
</form>
<?php elseif ($step === 2): ?>
<form method="post" action="cambio_password_email.php">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($email) ?>" readonly>

    <label for="code">Codice ricevuto via email</label>
    <input type="text" id="code" name="code" maxlength="6" required autofocus>

    <label for="new_password">Nuova Password</label>
    <input type="password" id="new_password" name="new_password" required>

    <label for="confirm_password">Conferma Nuova Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required>

    <button type="submit">Cambia Password</button>
</form>
<?php endif; ?>
</div>
</body>
</html>

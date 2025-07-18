<?php 
session_start();
require_once __DIR__ . '/../connect.php';

$message = '';

function randomPassword($length = 10) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $pass = '';
    for ($i = 0; $i < $length; $i++) {
        $pass .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $pass;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Email non valida.";
    } else {
        $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            $message = "Email non trovata.";
        } else {
            $user = $res->fetch_assoc();

            $newPass = randomPassword(12);
            $hashPass = password_hash($newPass, PASSWORD_BCRYPT);

            $stmt2 = $conn->prepare("UPDATE Customer SET password = ? WHERE email = ?");
            $stmt2->bind_param("ss", $hashPass, $email);

            if ($stmt2->execute()) {
                // Login automatico: setta sessione
                $_SESSION['email'] = $email;
                $_SESSION['customer_id'] = $user['customer_id'];

                // Invia email con password temporanea
                $subject = "Nuova password temporanea - Che Capelli";
                $messageBody = "Ciao,\n\nLa tua nuova password temporanea è: $newPass\nTi consigliamo di cambiarla subito dopo il login.\n\nSaluti,\nTeam Che Capelli";
                $headers = "From: noreply@checapelli.ittvive.it\r\nReply-To: noreply@checapelli.ittvive.it";

                mail($email, $subject, $messageBody, $headers);

                // Reindirizza alla pagina reset_password_temp.php
                header("Location: https://checapelli.ittvive.it/add-edit/reset_password_temp.php");
                exit;
            } else {
                $message = "Errore durante l'aggiornamento della password.";
            }
            $stmt2->close();
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="it">
<head>
<link rel="icon" href=".././style/rullino/icon.png" type="image/png">
<meta charset="UTF-8" />
<title>Reset Password - Che Capelli</title>
<link rel="stylesheet" href=".././style/style_input.css">
</head>
<body>
<div class="container">
<h2>Recupero Password</h2>

<?php if ($message !== ''): ?>
  <p class="message"><?= htmlspecialchars($message) ?></p>
<?php endif; ?>

<form method="post" action="cambio_password_email.php">
  <label for="email">Inserisci la tua Email</label>
  <input type="email" id="email" name="email" required autofocus />
  <button type="submit">Invia Password Temporanea</button>
</form>
</div>
</body>
</html>

<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Se l'utente è già loggato, lo mando alla dashboard
if (isset($_SESSION['email'])) {
    header("Location: ../view-get/menu.php");
    exit();
}

require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/cript.php';

$erroreLogin = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    $query = "SELECT * FROM Customer WHERE email = ?";
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        die("❌ Errore prepare(): " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $hashedPassword = $user["password"];

        if (password_verify($password, $hashedPassword)) {
            $_SESSION['customer_id'] = $user["customer_id"];
            $_SESSION['email'] = $email;

            header("Location: ../view-get/menu.php");
            exit();
        } else {
            $erroreLogin = "❌ Password errata.";
        }
    } else {
        $erroreLogin = "❌ Email non trovata.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Che Capelli - Login</title>
    <link rel="stylesheet" href="../style/style_input.css">
</head>
<body>
<div class="logo-container">
    <a href="../view-get/menu.php">
        <img src="../style/rullino/logo.png" alt="Che Capelli Logo" class="logo">
    </a>
</div>

<div class="form-container">
    <h2>Login</h2>

    <?php if (!empty($erroreLogin)): ?>
        <div style="color: red; margin-bottom: 10px;"><?php echo $erroreLogin; ?></div>
    <?php endif; ?>

    <form action="" method="POST">
        <label for="email">Email</label>
        <input type="email" name="email" required placeholder="Inserisci">

        <label for="password">Password</label>
        <input type="password" name="password" required placeholder="Inserisci">
        
        <button type="submit">Accedi</button>

        <a href="../add-edit/password_dimenticata.php" class="forgot-password">Password dimenticata?</a>
        <a href="../add-edit/index.php" class="forgot-password">Non hai un account? Registrati</a>
    </form>
</div>
</body>
</html>

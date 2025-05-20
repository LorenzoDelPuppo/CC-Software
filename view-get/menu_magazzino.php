<?php
session_start();
require_once __DIR__ . '/../connect.php';

// Imposta il ruolo di default come "ospite"
$userType = "ospite";

// Se l'utente è loggato, recupera il ruolo dal database
if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $sql = "SELECT user_tipe FROM Customer WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($dbUserType);
        if ($stmt->fetch()) {
            $userType = $dbUserType;
        }
        $stmt->close();
    }
    $conn->close();
}

// Se non è admin né operatrice, reindirizza al login
if ($userType !== "amministratore" && $userType !== "operatrice") {
    header("Location: .././add-edit/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
  <meta charset="UTF-8">
  <title>Menu Gestione Magazzino</title>
  <script src=".././js/menu_profilo.js" defer></script>
  <link rel="stylesheet" href=".././style/style_menu.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<?php include '.././view-get/barra.php'; ?>
<body>
<div class="menu-container">
  <!-- Qui puoi mettere direttamente i pulsanti perché l'accesso è garantito solo ad admin e operatrici -->
  <button class="menu-button" onclick="window.location.href='.././magazzino/lettore_scanner.php'">Scanner</button>
  <button class="menu-button" onclick="window.location.href='.././magazzino/magazzino_show.php'">Controlla Magazzino</button>
</div>
</body>
</html>

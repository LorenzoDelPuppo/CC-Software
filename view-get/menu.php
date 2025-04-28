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
?>
<!DOCTYPE html>
<html lang="it">
  
<head>
  <meta charset="UTF-8">
  <title>Menu</title>
  <script src=".././js/menu_profilo.js" defer></script>
  <link rel="stylesheet" href=".././style/style_menu.css">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<?php include '.././view-get/barra.php'; ?>
<body>
<div class="menu-container">
  <?php if ($userType === "amministratore"): ?>
    <h1>Menu Amministratore</h1>
    <button class="menu-button" onclick="window.location.href='.././view-get/dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/prenota.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/aggiungi_utente.php'">Aggiungi Utente</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/miei_appuntamenti.php'">Miei Appuntamenti</button>
    
  
  <?php elseif ($userType === "operatrice"): ?>
    <h1>Menu Operatrice</h1>
    <button class="menu-button" onclick="window.location.href='.././view-get/dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/dashboardDef.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/lista_clienti.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/cerca_appuntamento.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/prenota.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/aggiungi_utente.php'">Aggiungi Utente</button>
    
  <?php elseif ($userType === "cliente"): ?>
    <h1>Menu Cliente</h1>
    <button class="menu-button" onclick="window.location.href='.././add-edit/prenota.php'">Prenota</button>
    <button class="menu-button" onclick="window.location.href='.././view-get/miei_appuntamenti.php'">Miei Appuntamenti</button>
    
  <?php else: ?>
    <h1>Menu Ospite</h1>
    <button class="menu-button" onclick="window.location.href='home.php'">Home</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/login.php'">Login</button>
    <button class="menu-button" onclick="window.location.href='.././add-edit/index.php'">Registrati</button>
  <?php endif; ?>
</div>
</body>
</html>

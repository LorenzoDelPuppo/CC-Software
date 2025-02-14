<?php
session_start();
require_once 'connect.php';

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
  <script src="menu_profilo.js" defer></script>
  <link rel="stylesheet" href="style/barra_alta.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
<div class="menu-container">
  <?php if ($userType === "amministratore"): ?>
    <h1>Menu Amministratore</h1>
    <button class="menu-button" onclick="window.location.href='dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='prenota.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_utente.php'">Aggiungi Utente</button>
    <button class="menu-button" onclick="window.location.href='miei_appuntamenti.php'">Miei Appuntamenti</button>
    
  
  <?php elseif ($userType === "operatrice"): ?>
    <h1>Menu Operatrice</h1>
    <button class="menu-button" onclick="window.location.href='dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='prenota.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_utente.php'">Aggiungi Utente</button>
    
  <?php elseif ($userType === "cliente"): ?>
    <h1>Menu Cliente</h1>
    <button class="menu-button" onclick="window.location.href='prenota.php'">Prenota</button>
    <button class="menu-button" onclick="window.location.href='miei_appuntamenti.php'">Miei Appuntamenti</button>
    
  <?php else: ?>
    <h1>Menu Ospite</h1>
    <button class="menu-button" onclick="window.location.href='home.php'">Home</button>
    <button class="menu-button" onclick="window.location.href='login.php'">Login</button>
    <button class="menu-button" onclick="window.location.href='index.php'">Registrati</button>
  <?php endif; ?>
</div>
</body>
</html>

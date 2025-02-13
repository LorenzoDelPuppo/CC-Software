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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    /* Stili di base per il menu */
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f4;
      margin: 0;
      padding: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
    }
    .menu-container {
      background: #fff;
      padding: 20px;
      border-radius: 5px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.3);
      text-align: center;
      max-width: 400px;
      width: 90%;
    }
    .menu-container h1 {
      margin-bottom: 20px;
    }
    .menu-button {
      display: block;
      width: 90%;
      padding: 10px;
      margin: 10px auto;
      font-size: 16px;
      background: #007BFF;
      color: #fff;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    .menu-button:hover {
      background: #0056b3;
    }
  </style>
</head>
<body>
<div class="menu-container">
  <?php if ($userType === "amministratore"): ?>
    <h1>Menu Amministratore</h1>
    <button class="menu-button" onclick="window.location.href='dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_appuntamento.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_utente.php'">Aggiungi Utente</button>
    <button class="menu-button" onclick="window.location.href='index.php'">Home</button>
    <button class="menu-button" onclick="window.location.href='login.php'">Login</button>
    <button class="menu-button" onclick="window.location.href='profilo.php'">Profilo</button>
  <?php elseif ($userType === "operatrice"): ?>
    <h1>Menu Operatrice</h1>
    <button class="menu-button" onclick="window.location.href='dashboard.php'">Dashboard</button>
    <button class="menu-button" onclick="window.location.href='calendario.php'">Calendario</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_appuntamento.php'">Aggiungi Appuntamento</button>
    <button class="menu-button" onclick="window.location.href='aggiungi_utente.php'">Aggiungi Utente</button>
    <button class="menu-button" onclick="window.location.href='profilo.php'">Profilo</button>
  <?php elseif ($userType === "cliente"): ?>
    <h1>Menu Cliente</h1>
    <button class="menu-button" onclick="window.location.href='prenota.php'">Prenota</button>
    <button class="menu-button" onclick="window.location.href='miei_appuntamenti.php'">Miei Appuntamenti</button>
    <button class="menu-button" onclick="window.location.href='profilo.php'">Profilo</button>
  <?php else: ?>
    <h1>Menu Ospite</h1>
    <button class="menu-button" onclick="window.location.href='index.php'">Home</button>
    <button class="menu-button" onclick="window.location.href='login.php'">Login</button>
    <button class="menu-button" onclick="window.location.href='registrazione.php'">Registrati</button>
  <?php endif; ?>
</div>
</body>
</html>

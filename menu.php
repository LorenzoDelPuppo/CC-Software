<?php
// Avvia la sessione (se necessario per verificare eventuali informazioni utente)
session_start();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Menu Utente</title>
  <style>
    /* Impostazioni di base per l'intera pagina */
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #74ABE2, #5563DE);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #333;
    }
    /* Contenitore centrale per il menu */
    .menu-container {
      background: #fff;
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
      text-align: center;
      width: 90%;
      max-width: 400px;
    }
    /* Titolo della pagina */
    .menu-container h1 {
      margin-bottom: 40px;
      font-size: 2em;
      color: #333;
    }
    /* Stile dei pulsanti */
    .menu-button {
      display: block;
      width: 100%;
      padding: 15px;
      margin: 15px 0;
      font-size: 1.2em;
      color: #fff;
      background-color: #007BFF;
      border: none;
      border-radius: 5px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      text-decoration: none;
    }
    .menu-button:hover {
      background-color: #0056b3;
    }
  </style>
</head>
<body>
  <div class="menu-container">
    <h1>Area Utente</h1>
    <button class="menu-button" onclick="window.location.href='prenota.php'">
      Prenota Appuntamento
    </button>
    <button class="menu-button" onclick="window.location.href='miei_appuntamenti.php'">
      I miei Appuntamenti
    </button>
    <button class="menu-button" onclick="window.location.href='profilo.php'">
      Profilo
    </button>
  </div>
</body>
</html>

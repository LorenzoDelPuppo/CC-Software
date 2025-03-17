<?php
session_start();
require_once 'connect.php'; // Connessione al database

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Accesso negato!'); window.location.href = 'login.php';</script>";
    exit();
}

$email = $_SESSION['email'];

$stmt = $conn->prepare("SELECT user_tipe FROM Customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "<script>alert('Utente non trovato!'); window.location.href = 'login.php';</script>";
    exit();
}

$user = $result->fetch_assoc();
$userType = $user['user_tipe'];

if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    echo "<script>alert('Accesso non autorizzato!'); window.location.href = 'index.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scanner Barcode</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
  <style>
    #scanner-container { width: 100%; max-width: 640px; height: 480px; margin: auto; background: #000; }
    #barcode-result { font-size: 1.5em; text-align: center; margin-top: 20px; color: green; }
    .controls { text-align: center; margin: 10px 0; }
    button, select, input { margin: 5px; padding: 10px; font-size: 1em; }
  </style>
</head>
<body>
  <h2 style="text-align: center;">Scannerizza il codice a barre</h2>
  <div id="scanner-container"></div>
  <p id="barcode-result"></p>
  <button id="start-scan">Avvia Scanner</button>
  <script>
    let scannerRunning = false;
    
    function startScanner() {
      if (scannerRunning) {
        Quagga.stop();
        scannerRunning = false;
        document.getElementById("start-scan").innerText = "Avvia Scanner";
        return;
      }
      
      Quagga.init({
        inputStream: { type: "LiveStream", target: document.getElementById("scanner-container") },
        decoder: { readers: ["ean_reader", "ean_8_reader", "code_128_reader"] },
        locate: true
      }, function(err) {
        if (err) { console.error(err); return; }
        Quagga.start();
        scannerRunning = true;
        document.getElementById("start-scan").innerText = "Ferma Scanner";
      });
      
      Quagga.onDetected(function(result) {
        document.getElementById("barcode-result").innerText = "Codice: " + result.codeResult.code;
        Quagga.stop();
        scannerRunning = false;
        document.getElementById("start-scan").innerText = "Avvia Scanner";
      });
    }
    
    document.getElementById("start-scan").addEventListener("click", startScanner);
  </script>
</body>
</html>

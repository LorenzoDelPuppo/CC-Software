<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['email'])) {
    echo "<script>alert('Accesso negato!'); window.location.href = './add-edit/login.php';</script>";
    exit();
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT user_tipe FROM Customer WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
if ($result->num_rows == 0 || ($user['user_tipe'] !== 'amministratore' && $user['user_tipe'] !== 'operatrice')) {
    echo "<script>alert('Accesso non autorizzato!'); window.location.href = 'index.php';</script>";
    exit();
}

$messaggio = '';

// Gestione modifica quantità
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['azione'], $_POST['quantita'], $_POST['cod_p']) && !isset($_POST['aggiungi_nuovo'])) {
    $codice = $_POST['cod_p'];
    $quantita = intval($_POST['quantita']);
    $azione = $_POST['azione'];

    if ($azione === 'togli') {
        $quantita = -$quantita;
    }

    $stmt = $conn->prepare("UPDATE magazzino SET QTA = QTA + ? WHERE cod_p = ?");
    $stmt->bind_param("is", $quantita, $codice);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $messaggio = "<p style='color:green;'>Magazzino aggiornato con successo.</p>";
    } else {
        $messaggio = "<p style='color:red;'>Errore nell'aggiornamento.</p>";
    }
}

// Gestione inserimento nuovo prodotto
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['aggiungi_nuovo'])) {
    $nome = $_POST['nome_p'];
    $codice = $_POST['cod_p'];
    $qta = intval($_POST['qta']);

    // Controllo se già esiste
    $check = $conn->prepare("SELECT * FROM magazzino WHERE cod_p = ?");
    $check->bind_param("s", $codice);
    $check->execute();
    $check_result = $check->get_result();
    if ($check_result->num_rows > 0) {
        $messaggio = "<p style='color:red;'>Il prodotto con questo codice esiste già!</p>";
    } else {
        $insert = $conn->prepare("INSERT INTO magazzino (nome_p, cod_p, QTA) VALUES (?, ?, ?)");
        $insert->bind_param("ssi", $nome, $codice, $qta);
        if ($insert->execute()) {
            $messaggio = "<p style='color:green;'>Prodotto aggiunto con successo!</p>";
        } else {
            $messaggio = "<p style='color:red;'>Errore durante l'inserimento.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <title>Gestione Magazzino - Scanner</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
  <style>
    #scanner-container { width: 100%; max-width: 640px; height: 480px; margin: auto; background: #000; }
    #barcode-result, #modifica-form, #messaggio { font-size: 1.2em; text-align: center; margin-top: 20px; }
    .controls { text-align: center; margin: 10px 0; }
    button, select, input { margin: 5px; padding: 10px; font-size: 1em; }
  </style>
</head>
<body>
  <h2 style="text-align: center;">Scannerizza un codice a barre</h2>
  <div id="scanner-container"></div>
  <div id="messaggio"><?= $messaggio ?></div>
  <p id="barcode-result"></p>
  <div id="modifica-form"></div>
  <div class="controls">
    <button id="start-scan">Avvia Scanner</button>
  </div>

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

      Quagga.onDetected(async function(result) {
        const codice = result.codeResult.code;
        document.getElementById("barcode-result").innerText = "Codice rilevato: " + codice;

        const response = await fetch(window.location.href, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: "check_only=1&codice=" + encodeURIComponent(codice)
        });

        const html = await response.text();
        document.getElementById("modifica-form").innerHTML = html;

        Quagga.stop();
        scannerRunning = false;
        document.getElementById("start-scan").innerText = "Avvia Scanner";
      });
    }

    document.getElementById("start-scan").addEventListener("click", startScanner);
  </script>

<?php
// Risposta AJAX interna
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['check_only'], $_POST['codice'])) {
    $codice = $_POST['codice'];
    $stmt = $conn->prepare("SELECT * FROM magazzino WHERE cod_p = ?");
    $stmt->bind_param("s", $codice);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        echo "
        <form method='POST' style='text-align:center;'>
            <h3>Prodotto non trovato!</h3>
            <input type='hidden' name='cod_p' value='" . htmlspecialchars($codice) . "'>
            <input type='hidden' name='aggiungi_nuovo' value='1'>
            <label>Nome prodotto:</label><br>
            <input type='text' name='nome_p' required><br><br>
            <label>Quantità iniziale:</label><br>
            <input type='number' name='qta' min='1' required><br><br>
            <button type='submit'>Aggiungi al magazzino</button>
        </form>
        ";
    } else {
        $prodotto = $res->fetch_assoc();
        echo "
        <form method='POST' style='text-align:center;'>
            <h3>Prodotto: {$prodotto['nome_p']}</h3>
            <p>Quantità attuale: {$prodotto['QTA']}</p>
            <input type='hidden' name='cod_p' value='{$prodotto['cod_p']}'>
            <label>Operazione:</label>
            <select name='azione'>
                <option value='aggiungi'>Aggiungi</option>
                <option value='togli'>Togli</option>
            </select><br>
            <label>Quantità:</label>
            <input type='number' name='quantita' min='1' required><br>
            <button type='submit'>Conferma</button>
        </form>
        ";
    }

    exit();
}
?>
</body>
</html>

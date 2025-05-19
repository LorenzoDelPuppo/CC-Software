<?php
session_start();
require_once __DIR__ . '/../connect.php';

// === Controllo accesso utente ===
if (!isset($_SESSION['email'])) {
    header("Location: ../add-edit/login.php");
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    $email = $_SESSION['email'];
    $query = "SELECT customer_id FROM Customer WHERE email = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['customer_id'] = $row['customer_id'];
        } else {
            die("Errore: Cliente non trovato.");
        }
        $stmt->close();
    } else {
        die("Errore nella preparazione della query.");
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
  <meta charset="UTF-8">
  <title>Lettore Codici a Barre</title>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>
  <style>
    #scanner-container { width: 100%; max-width: 640px; height: 480px; margin: auto; background: #000; }
    #barcode-result { font-size: 1.5em; text-align: center; margin-top: 20px; color: green; }
    .controls, form { text-align: center; margin: 20px auto; }
    button, input { margin: 5px; padding: 10px; font-size: 1em; }
  </style>
</head>
<body>
  <h2 style="text-align: center;">Scannerizza un prodotto</h2>
  <div id="scanner-container"></div>
  <p id="barcode-result"></p>
  <div class="controls">
    <button id="start-scan">Avvia Scanner</button>
  </div>
  <div id="prodotto-container"></div>

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
        const codice = result.codeResult.code;
        document.getElementById("barcode-result").innerText = "Codice: " + codice;
        aggiornaMagazzinoUI(codice);
        Quagga.stop();
        scannerRunning = false;
        document.getElementById("start-scan").innerText = "Avvia Scanner";
      });
    }

    function aggiornaMagazzinoUI(codice) {
      fetch('controlla_magazzino.php?codice=' + codice)
        .then(res => res.json())
        .then(data => {
          const container = document.getElementById("prodotto-container");
          if (data.trovato) {
            container.innerHTML = `
<h3>Prodotto: ${data.nome}</h3>
<p>Quantità attuale: <strong>${data.qta}</strong></p>
<form onsubmit="salvaQta(event, '${codice}')">
  <label>Quantità da aggiungere:</label><br>
  <input type="number" id="qta_delta" name="qta_delta" required><br>
  <button type="submit">Salva Quantità</button>
</form>
`;
          } else {
            container.innerHTML = `
              <h3>Nuovo prodotto</h3>
              <form onsubmit="inserisciProdotto(event)">
                <label>Nome prodotto:</label><br>
                <input type="text" name="nome" required><br>
                <label>Codice:</label><br>
                <input type="text" name="codice" value="${codice}" required><br>
                <label>Quantità:</label><br>
                <input type="number" name="qta" value="1" required><br>
                <button type="submit">Aggiungi</button>
              </form>
            `;
          }
        });
    }

    function aggiornaQta(codice, delta) {
      fetch('aggiorna_quantita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codice: codice, delta: delta })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          document.getElementById("qta").innerText = data.nuova_qta;
        }
      });
    }

    function inserisciProdotto(e) {
      e.preventDefault();
      const form = e.target;
      const dati = {
        nome: form.nome.value,
        codice: form.codice.value,
        qta: parseInt(form.qta.value)
      };

      fetch('inserisci_prodotto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(dati)
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Prodotto inserito con successo!");
          aggiornaMagazzinoUI(dati.codice);
        } else {
          alert("Errore nell'inserimento.");
        }
      });
    }

    
    function modificaQta(delta) {
      const qtaInput = document.getElementById("qta");
      qtaInput.value = parseInt(qtaInput.value) + delta;
      if (qtaInput.value < 0) qtaInput.value = 0;
    }

    function salvaQta(codice) {
      const nuovaQta = parseInt(document.getElementById("qta").value);
      fetch('aggiorna_quantita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codice: codice, delta: 0, nuova_qta: nuovaQta })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Quantità aggiornata!");
          document.getElementById("qta").value = data.nuova_qta;
        } else {
          alert("Errore nel salvataggio.");
        }
      });
    }

    
    function aggiungiQuantita(event, codice) {
      event.preventDefault();
      const qta = parseInt(document.getElementById("aggiunta").value);
      fetch('aggiorna_quantita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codice: codice, delta: qta })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Quantità aggiornata!");
          aggiornaMagazzinoUI(codice);
        } else {
          alert("Errore durante l'aggiornamento.");
        }
      });
    }

    
    function salvaQta(event, codice) {
      event.preventDefault();
      const delta = parseInt(document.getElementById("qta_delta").value);
      fetch('aggiorna_quantita.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ codice: codice, delta: delta })
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert("Quantità aggiornata!");
          document.getElementById("barcode-result").innerText = "Codice: " + codice + " (Quantità aggiornata)";
          aggiornaMagazzinoUI(codice);
        } else {
          alert("Errore nel salvataggio.");
        }
      });
    }

    document.getElementById("start-scan").addEventListener("click", startScanner);
  </script>
</body>
</html>

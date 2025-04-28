<?php
// dashboarddef.php

session_start();

// Abilita il reporting degli errori per il debugging (rimuovi in produzione)
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../connect.php';

if (!isset($conn)) {
    die("Errore: Connessione al database non definita.");
}
if ($conn->connect_error) {
    die("Errore di connessione: " . $conn->connect_error);
}

// Controlla che in sessione sia presente un customer_id
if (!isset($_SESSION['customer_id'])) {
    header("Location: .././add-edit/login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Recupera il tipo utente dal database (assicurati che il campo user_tipe esista nella tabella customer)
$sql = "SELECT user_tipe FROM Customer WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$stmt->bind_result($user_tipe);
$stmt->fetch();
$stmt->close();

// Se il tipo utente non è 'amministratore' o 'operatrice', reindirizza al login
if ($user_tipe != 'amministratore' && $user_tipe != 'operatrice') {
    header("Location: .././add-edit/login.php");
    exit;
}

// Mappa dei colori per ogni servizio con colori più distinti
$serviceColors = array(
    'Piega'               => '#e74c3c', // rosso
    'Taglio'              => '#3498db', // blu
    'Colore'              => '#2ecc71', // verde
    'Mèche - Schiariture' => '#f1c40f', // giallo
    'Permanente'          => '#9b59b6', // viola
    'Stiratura'           => '#1abc9c', // turchese
    'Keratina'            => '#e84393', // rosa acceso
    'Colori - Mèche'      => '#34495e', // blu scuro/grigio
    'Ricostruzione'       => '#e67e22', // arancione
    'Trattamento'         => '#7f8c8d'  // grigio
);

function showAllAppuntamenti($conn) {
    global $serviceColors;
    $sql = "SELECT a.dateTime, c.fName, c.lName, 
                   GROUP_CONCAT(DISTINCT s.nameS ORDER BY s.nameS SEPARATOR ', ') AS servizi 
            FROM appointment a 
            JOIN Customer c ON a.Customer_id = c.Customer_id 
            LEFT JOIN mergeAS m ON a.appointment_id = m.appointment_id 
            LEFT JOIN serviceCC s ON m.service_id = s.service_id 
            GROUP BY a.dateTime, c.fName, c.lName 
            ORDER BY a.dateTime ASC";

    $result = $conn->query($sql);
    if (!$result) {
        die("Errore nella query: " . $conn->error);
    }
    
    echo "<table class='table table-striped'>";
    echo "<thead>
            <tr>
              <th>Data e Ora</th>
              <th>Nome Cliente</th>
              <th>Servizi Prenotati</th>
            </tr>
          </thead>
          <tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['dateTime'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['fName'] . " " . $row['lName']) . "</td>";
        // Mostra i pallini relativi ai servizi in una singola riga, allineati a destra
        echo "<td style='text-align: right;'>";
        if ($row['servizi']) {
            $serviziArray = explode(', ', $row['servizi']);
            foreach ($serviziArray as $service) {
                $service = trim($service);
                $color = isset($serviceColors[$service]) ? $serviceColors[$service] : 'gray';
                echo "<span class='dot' style='background-color: {$color};' title='" . htmlspecialchars($service) . "'></span>";
            }
        } else {
            echo 'Nessun servizio';
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</tbody></table>";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Appuntamenti</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    .dot {
      display: inline-block;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 5px;
    }
    /* Legenda dei servizi in colonna, fissata a sinistra */
    .service-legend {
      position: fixed;
      top: 100px;
      left: 20px;
      background-color: #f8f9fa;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .service-legend-item {
      display: flex;
      align-items: center;
    }
    .service-legend-item .legend-text {
      margin-left: 5px;
      font-size: 14px;
    }
  </style>
 <?php include '.././view-get/barra.php'; ?>
</head>
<body>
  <div class="container mt-4">
    <h2 class="mb-4">Tutti gli Appuntamenti Prenotati</h2>
    <?php showAllAppuntamenti($conn); ?>
    <div class="mt-4 d-flex justify-content-around">
      <a href=".././add-edit/aggiungi_utente.php" class="btn btn-primary">Aggiungi Cliente</a>
      <a href=".././add-edit/prenota.php" class="btn btn-success">Aggiungi Appuntamento</a>
      <a href=".././view-get/calendario.php" class="btn btn-info">Calendario</a>
      <a href=".././view-get/lista_clienti.php" class="btn btn-warning">Schede Clienti</a>
    </div>
  </div>
  <!-- Legenda dei servizi -->
  <div class="service-legend">
    <?php foreach($serviceColors as $serviceName => $color): ?>
      <div class="service-legend-item">
        <span class="dot" style="background-color: <?php echo $color; ?>;"></span>
        <span class="legend-text"><?php echo htmlspecialchars($serviceName); ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</body>
</html>

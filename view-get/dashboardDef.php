<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connect.php'; // Connessione al database

if (!isset($conn)) {
    die("Errore: Connessione al database non definita.");
}
if ($conn->connect_error) {
    die("Errore di connessione: " . $conn->connect_error);
}

function showAllAppuntamenti($conn) {
    $sql = "SELECT a.dateTime, c.fName, c.lName, 
                   GROUP_CONCAT(DISTINCT s.nameS ORDER BY s.nameS SEPARATOR ', ') AS servizi 
            FROM appointment a 
            JOIN customer c ON a.customer_id = c.customer_id 
            LEFT JOIN mergeas m ON a.appointment_id = m.appointment_id 
            LEFT JOIN servicecc s ON m.service_id = s.service_id 
            GROUP BY a.dateTime, c.fName, c.lName 
            ORDER BY a.dateTime ASC";

    $result = $conn->query($sql);

    if (!$result) {
        die("Errore nella query: " . $conn->error);
    }

    echo "<table class='table table-striped'>";
    echo "<thead><tr><th>Data e Ora</th><th>Nome Cliente</th><th>Servizi Prenotati</th></tr></thead><tbody>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['dateTime'])) . "</td>";
        echo "<td>" . htmlspecialchars($row['fName'] . " " . $row['lName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['servizi'] ? $row['servizi'] : 'Nessun servizio') . "</td>";
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
</body>
</html>

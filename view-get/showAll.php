<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../connect.php';// Inclusione della connessione al database

// Debug: Controlliamo se la connessione esiste
if (!isset($conn)) {
    die("Errore: Connessione al database non definita. Verifica il file require_once __DIR__ . '/../connect.php';.");
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

    echo "<table border='1'>";
    echo "<tr><th>Data e Ora</th><th>Nome Cliente</th><th>Servizi Prenotati</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['dateTime'] . "</td>";
        echo "<td>" . $row['fName'] . " " . $row['lName'] . "</td>";
        echo "<td>" . ($row['servizi'] ? $row['servizi'] : 'Nessun servizio') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
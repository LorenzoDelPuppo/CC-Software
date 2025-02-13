<?php
// Blocco di controllo accessi
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Recupera il ruolo dell'utente dal database
$sql = "SELECT user_tipe FROM Customer WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

// Controllo: solo amministratore e operatrice possono accedere
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}
?>
<!DOCTYPE html> 
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <!-- Pulsante per il Logout -->
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>

    <?php
    // La connessione al database è già stata aperta in cima; se serve riaprirla, richiedila
    require 'connect.php';

    // Recupero della data selezionata oppure uso quella di oggi
    $selectedDate = isset($_GET['date']) ? $_GET['date'] : date("Y-m-d");

    echo "<h2>Appuntamenti per il giorno: " . date("d-m-Y", strtotime($selectedDate)) . "</h2>";

    // Query per ottenere gli appuntamenti con nome cliente
    $sql = "
        SELECT 
            a.appointment_id, 
            TIME(a.dateTime) AS orario, 
            CONCAT(c.fName, ' ', c.lName) AS cliente
        FROM appointment a
        JOIN customer c ON a.customer_id = c.customer_id
        WHERE DATE(a.dateTime) = ?
        ORDER BY a.dateTime ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedDate);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<table border='1' style='width:100%; text-align:center; border-collapse: collapse;'>";
        echo "<tr><th>Orario</th><th>Cliente</th><th>Servizi Prenotati</th></tr>";

        while ($row = $result->fetch_assoc()) {
            $appointment_id = $row['appointment_id'];

            // Query per ottenere i servizi associati a questo appuntamento
            $sql_services = "
                SELECT s.nameS 
                FROM mergeAS m
                JOIN serviceCC s ON m.service_id = s.service_id
                WHERE m.appointment_id = ?
            ";
            $stmt_services = $conn->prepare($sql_services);
            $stmt_services->bind_param("i", $appointment_id);
            $stmt_services->execute();
            $result_services = $stmt_services->get_result();

            $services = [];
            while ($service = $result_services->fetch_assoc()) {
                $services[] = $service['nameS'];
            }
            $services_list = !empty($services) ? implode(", ", $services) : "Nessun servizio prenotato";

            echo "<tr>";
            echo "<td>" . $row['orario'] . "</td>";
            echo "<td>" . $row['cliente'] . "</td>";
            echo "<td>" . $services_list . "</td>";
            echo "</tr>";
        }

        echo "</table>";
    } else {
        echo "<p>Nessun appuntamento trovato per questa data.</p>";
    }

    $conn->close();
    ?>

    <!-- Sezione pulsanti -->
    <div class="button-container" style="display: flex; gap: 10px;">
        <form action="prenota.php" method="post">
            <button type="submit">Aggiungi Appuntamento</button>
        </form>
        <form action="calendario.php" method="get">
            <button type="submit">Calendario</button>
        </form>
        <form action="clienti.php" method="post">
            <button type="submit">Gestione Clienti</button>
        </form>
        <form action="report.php" method="post">
            <button type="submit">Report</button>
        </form>
    </div>
</body>
</html>

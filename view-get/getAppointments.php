<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../connect.php';// Connessione MySQLi

if (!isset($_GET['date']) || empty($_GET['date'])) {
    echo json_encode(["error" => "Data non fornita"]);
    exit;
}

$date = $_GET['date']; // Data selezionata nel formato YYYY-MM-DD

// Query per recuperare gli appuntamenti con i servizi associati
$query = "
    SELECT 
        a.appointment_id, 
        DATE_FORMAT(DATE_ADD(a.dateTime, INTERVAL 0 MINUTE), '%H:%i') AS startTime, 
        c.fName, 
        c.lName, 
        s.nameS, 
        s.engageTime 
    FROM appointment a
    JOIN Customer c ON a.customer_id = c.customer_id
    LEFT JOIN mergeAS m ON a.appointment_id = m.appointment_id
    LEFT JOIN serviceCC s ON m.service_id = s.service_id
    WHERE DATE(a.dateTime) = ?
    ORDER BY a.dateTime
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointmentId = $row['appointment_id'];

    // Creazione dell'appuntamento se non esiste nell'array
    if (!isset($appointments[$appointmentId])) {
        $appointments[$appointmentId] = [
            "appointment_id" => $appointmentId,
            "startTime" => $row['startTime'],
            "customer" => $row['fName'] . " " . $row['lName'],
            "services" => [],
            "totalDuration" => 0
        ];
    }

    // Aggiunta del servizio solo se presente
    if (!is_null($row['nameS'])) {
        $appointments[$appointmentId]["services"][] = [
            "name" => $row['nameS'],
            "duration" => (int) $row['engageTime']
        ];
        $appointments[$appointmentId]["totalDuration"] += (int) $row['engageTime'];
    }
}

// Chiudi la connessione
$stmt->close();
$conn->close();

// Stampa JSON
echo json_encode(array_values($appointments), JSON_PRETTY_PRINT);
?>
    
<?php
// available_slots.php
require_once 'connect.php'; // Include la connessione al database

// Imposta l'header per il JSON
header("Content-Type: application/json");

// Recupera la data passata via GET (es. ?date=2025-02-15)
$date = $_GET['date'] ?? '';
if (!$date) {
    echo json_encode(['error' => 'Data non fornita']);
    exit;
}

// Converte la data in timestamp (per eventuali controlli)
$timestamp = strtotime($date);
if ($timestamp === false) {
    echo json_encode(['error' => 'Data non valida']);
    exit;
}

// Recupera dal database gli appuntamenti già prenotati per questa data
$query = "SELECT dateTime FROM appointment WHERE DATE(dateTime) = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookedSlots = [];
    while ($row = $result->fetch_assoc()) {
        // Salviamo solo l'orario in formato "HH:MM"
        $bookedSlots[] = date("H:i", strtotime($row['dateTime']));
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Errore nella query']);
    exit;
}

// Calcola il giorno della settimana (1 = lunedì, 7 = domenica)
$dayNumber = (int)date('N', $timestamp);
// Usa la funzione/class WeekDay per ottenere gli orari disponibili di default
$allSlots = WeekDay::getSlots($dayNumber);

// Rimuove dagli slot disponibili quelli già occupati
$freeSlots = array_values(array_diff($allSlots, $bookedSlots));

// Restituisce il risultato in formato JSON
echo json_encode($freeSlots);
?>

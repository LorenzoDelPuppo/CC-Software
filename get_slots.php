<?php
session_start();
require_once 'connect.php'; // Assicurati che questo file contenga i dati per la connessione al DB

// --- Funzioni Helper ---
function timeToMinutes(string $timeStr): int {
    [$hours, $minutes] = explode(":", $timeStr);
    return ((int)$hours * 60) + (int)$minutes;
}

function minutesToTime(int $minutes): string {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf("%02d:%02d", $hours, $mins);
}

function generateSlots(string $startTime, string $endTime, int $interval): array {
    $startMinutes = timeToMinutes($startTime);
    $endMinutes   = timeToMinutes($endTime);
    $slots = [];
    for ($time = $startMinutes; $time + $interval <= $endMinutes; $time += $interval) {
        $slots[] = minutesToTime($time);
    }
    return $slots;
}

// --- Classe per gestire gli orari in base al giorno ---
class WeekDay {
    public const MONDAY    = 1;
    public const TUESDAY   = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY  = 4;
    public const FRIDAY    = 5;
    public const SATURDAY  = 6;
    public const SUNDAY    = 7;
    
    public static function getSlots(int $day): array {
        // Supponiamo che lunedì e domenica non siano disponibili
        if ($day === self::MONDAY || $day === self::SUNDAY) {
            return [];
        }
        if ($day === self::SATURDAY) {
            return generateSlots("08:00", "17:00", 15);
        }
        // Martedì - Venerdì: due fasce (mattina e pomeriggio)
        $morning   = generateSlots("08:30", "12:30", 15);
        $afternoon = generateSlots("15:00", "19:00", 15);
        return array_merge($morning, $afternoon);
    }
}

// --- Ricezione e validazione dei parametri ---
$date = $_GET['date'] ?? '';
$requiredDuration = isset($_GET['duration']) ? intval($_GET['duration']) : 0;

if (!$date || $requiredDuration <= 0) {
    echo json_encode(['error' => 'Parametri non validi']);
    exit;
}

$timestamp = strtotime($date);
if (!$timestamp) {
    echo json_encode(['error' => 'Data non valida']);
    exit;
}

$dayNumber = (int)date('N', $timestamp);
$allSlots = WeekDay::getSlots($dayNumber);

// --- Recupero degli appuntamenti già prenotati per la data ---
// Per ciascun appuntamento la durata viene calcolata sommando il campo "timeTOT" dei servizi prenotati
$sql = "SELECT a.dateTime, SUM(sc.timeTOT) as duration 
        FROM appointment a 
        JOIN mergeAS mas ON a.appointment_id = mas.appointment_id
        JOIN serviceCC sc ON mas.service_id = sc.service_id
        WHERE DATE(a.dateTime) = ?
        GROUP BY a.appointment_id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $startTime = date("H:i", strtotime($row['dateTime']));
    $duration  = intval($row['duration']);
    $appointments[] = [
        'start' => timeToMinutes($startTime),
        'end'   => timeToMinutes($startTime) + $duration
    ];
}
$stmt->close();

// --- Filtraggio degli slot disponibili ---
// Per ciascun slot, se il numero di appuntamenti che si sovrappongono (calcolati in base alla durata richiesta)
// è minore di 2, allora lo slot è disponibile
$availableSlots = [];
foreach ($allSlots as $slot) {
    $slotStart = timeToMinutes($slot);
    $slotEnd   = $slotStart + $requiredDuration;
    $overlapCount = 0;
    foreach ($appointments as $appt) {
        if ($slotStart < $appt['end'] && $slotEnd > $appt['start']) {
            $overlapCount++;
        }
    }
    if ($overlapCount < 2) {  // se già prenotati 0 o 1 appuntamenti, lo slot è disponibile
        $availableSlots[] = $slot;
    }
}

echo json_encode($availableSlots);
$conn->close();
?>
    
<?php
session_start();
require_once 'connect.php'; // Connessione al database

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    die("Errore: Devi effettuare il login per prenotare un appuntamento.");
}

// Se il customer_id non è già in sessione, recuperalo dal database usando l'email
if (!isset($_SESSION['customer_id'])) {
    $email = $_SESSION['email'];
    $query = "SELECT customer_id FROM customer WHERE email = ?";
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
$customer_id = $_SESSION['customer_id'];

$message = "";

/* ======================
   Funzioni Helper per la gestione degli orari
   ====================== */

// Converte una stringa "HH:MM" in minuti totali dal mezzanotte
function timeToMinutes(string $timeStr): int {
    [$hours, $minutes] = explode(":", $timeStr);
    return ((int)$hours * 60) + (int)$minutes;
}

// Converte minuti totali in una stringa "HH:MM" formattata
function minutesToTime(int $minutes): string {
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return sprintf("%02d:%02d", $hours, $mins);
}

// Genera una serie di slot (orari) dati l'orario di inizio e fine, con un intervallo (in minuti).
// L'ultimo slot viene generato solo se, aggiungendo l'intervallo, non si supera l'orario di chiusura.
function generateSlots(string $startTime, string $endTime, int $interval): array {
    $startMinutes = timeToMinutes($startTime);
    $endMinutes = timeToMinutes($endTime);
    $slots = [];
    for ($time = $startMinutes; $time + $interval <= $endMinutes; $time += $interval) {
        $slots[] = minutesToTime($time);
    }
    return $slots;
}

/* ======================
   Enum per i giorni della settimana
   ====================== */
enum WeekDay: int {
    case MONDAY    = 1;
    case TUESDAY   = 2;
    case WEDNESDAY = 3;
    case THURSDAY  = 4;
    case FRIDAY    = 5;
    case SATURDAY  = 6;
    case SUNDAY    = 7;
    
    // Restituisce gli slot di appuntamento in base al giorno:
    // - Lunedì e Domenica: array vuoto
    // - Sabato: slot dalle 08:00 alle 17:00 (intervallo 15 minuti)
    // - Martedì-Venerdì: due fasce (08:30-12:30 e 15:00-19:00)
    public function getSlots(): array {
        if ($this === self::MONDAY || $this === self::SUNDAY) {
            return [];
        }
        if ($this === self::SATURDAY) {
            return generateSlots("08:00", "17:00", 15);
        }
        // Per TUESDAY, WEDNESDAY, THURSDAY, FRIDAY:
        $morning = generateSlots("08:30", "12:30", 15);
        $afternoon = generateSlots("15:00", "19:00", 15);
        return array_merge($morning, $afternoon);
    }
}

/* ======================
   Gestione del form
   ====================== */

// Flag per indicare se mostrare il dropdown degli orari (step 1: data selezionata ma orario non ancora scelto)
$show_time_slot = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])) {
        $appointment_date_only = $_POST['appointment_date']; // formato: "YYYY-MM-DD"
        
        // Se l'orario non è stato ancora selezionato, mostra il dropdown
        if (!isset($_POST['time_slot']) || empty($_POST['time_slot'])) {
            $show_time_slot = true;
        } else {
            // Entrambi i campi sono compilati: combino la data e l'orario per ottenere un datetime
            $time_slot = $_POST['time_slot']; // formato: "HH:MM"
            $appointment_datetime = $appointment_date_only . " " . $time_slot . ":00";
            
            // Inserisce un nuovo appuntamento nella tabella "appointment"
            $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("is", $customer_id, $appointment_datetime);
                if ($stmt->execute()) {
                    $appointment_id = $stmt->insert_id; // Ottiene l'ID dell'appuntamento
                    $message .= "Appuntamento prenotato con successo!<br>";
                    
                    // Se sono state selezionate delle checkbox, inserisce i servizi correlati
                    if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
                        $sql2 = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                        if ($stmt2 = $conn->prepare($sql2)) {
                            foreach ($_POST['checkboxes'] as $service_id) {
                                $service_id = intval($service_id);
                                $sPera = ""; // Valore di default per sPera, da modificare in base alle esigenze
                                $stmt2->bind_param("iis", $appointment_id, $service_id, $sPera);
                                $stmt2->execute();
                            }
                            $stmt2->close();
                        } else {
                            $message .= "Errore nella preparazione della query per i servizi.";
                        }
                    } else {
                        $message .= "Nessun servizio selezionato.";
                    }
                } else {
                    $message .= "Errore durante l'inserimento dell'appuntamento: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $message .= "Errore nella preparazione della query per l'appuntamento.";
            }
        }
    } else {
        $message .= "Devi selezionare la data dell'appuntamento.<br>";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenotazione Appuntamenti</title>
    <!-- Includi jQuery e jQuery UI per il datepicker -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        // Relazioni obbligatorie e incompatibilità per i servizi (già presenti)
        const mandatoryRelations = {
            2: [1],
            3: [1],
            4: [1],
            5: [1],
            7: [1],
            8: [1],
            9: [1]
        };

        const incompatibleRelations = {
            3: [7],
            4: [7, 8],
            5: [6, 7],
            6: [5, 8],
            7: [3, 4, 5, 6],
            8: [3, 4, 6]
        };

        function updateCheckboxStates(checkbox) {
            const selectedValue = parseInt(checkbox.value);
            
            // Se la checkbox è selezionata, attiva quelle obbligatorie
            if (checkbox.checked && mandatoryRelations[selectedValue]) {
                mandatoryRelations[selectedValue].forEach(value => {
                    const relatedCheckbox = document.querySelector(`input[value="${value}"]`);
                    if (relatedCheckbox) {
                        relatedCheckbox.checked = true;
                    }
                });
            }
            // Gestione delle checkbox incompatibili
            document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                const cbValue = parseInt(cb.value);
                if (incompatibleRelations[selectedValue] && incompatibleRelations[selectedValue].includes(cbValue)) {
                    if (checkbox.checked) {
                        cb.checked = false;
                        cb.disabled = true;
                    } else {
                        cb.disabled = false;
                    }
                }
            });
        }

        $(document).ready(function(){
            $("#appointment_date").datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>
</head>
<body>
    <h1>Servizi</h1>
    <?php
    if (!empty($message)) {
        echo "<p>$message</p>";
    }
    ?>
    <form method="post">
        <fieldset>
            <legend>Seleziona i servizi</legend>
            <input type="checkbox" name="checkboxes[]" value="1" onchange="updateCheckboxStates(this)"> Piega<br>
            <input type="checkbox" name="checkboxes[]" value="2" onchange="updateCheckboxStates(this)"> Taglio<br>
            <input type="checkbox" name="checkboxes[]" value="3" onchange="updateCheckboxStates(this)"> Colore<br>
            <input type="checkbox" name="checkboxes[]" value="4" onchange="updateCheckboxStates(this)"> Mèche/Schiariture<br>
            <input type="checkbox" name="checkboxes[]" value="5" onchange="updateCheckboxStates(this)"> Permanente<br>
            <input type="checkbox" name="checkboxes[]" value="6" onchange="updateCheckboxStates(this)"> Stiratura<br>
            <input type="checkbox" name="checkboxes[]" value="7" onchange="updateCheckboxStates(this)"> Keratina<br>
            <input type="checkbox" name="checkboxes[]" value="8" onchange="updateCheckboxStates(this)"> Colore - Mèche<br>
            <input type="checkbox" name="checkboxes[]" value="9" onchange="updateCheckboxStates(this)"> Ricostruzione<br>
            <input type="checkbox" name="checkboxes[]" value="10" onchange="updateCheckboxStates(this)"> Trattamento<br>
        </fieldset>
        <fieldset>
            <legend>Seleziona la data dell'appuntamento</legend>
            <label for="appointment_date">Data Appuntamento:</label>
            <input type="text" id="appointment_date" name="appointment_date" autocomplete="off" value="<?= isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : '' ?>">
        </fieldset>
        <?php
        // Se è stata selezionata una data, mostra il dropdown per la scelta dell'orario
        if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])):
            $selectedDate = $_POST['appointment_date'];
            $timestamp = strtotime($selectedDate);
            if ($timestamp !== false) {
                // Ottiene il numero del giorno (1 = lunedì, 7 = domenica)
                $dayNumber = (int)date('N', $timestamp);
                // Crea l'enum corrispondente e recupera gli slot disponibili
                $weekDay = WeekDay::from($dayNumber);
                $availableSlots = $weekDay->getSlots();
                if (empty($availableSlots)) {
                    echo "<p>Nessun appuntamento disponibile per il giorno selezionato.</p>";
                } else {
                    echo '<fieldset>';
                    echo '<legend>Seleziona l\'orario dell\'appuntamento</legend>';
                    echo '<label for="time_slot">Orario:</label>';
                    echo '<select name="time_slot" id="time_slot">';
                    foreach ($availableSlots as $slot) {
                        $selected = (isset($_POST['time_slot']) && $_POST['time_slot'] == $slot) ? ' selected' : '';
                        echo "<option value='" . htmlspecialchars($slot) . "'$selected>" . htmlspecialchars($slot) . "</option>";
                    }
                    echo '</select>';
                    echo '</fieldset>';
                }
            }
        endif;
        ?>
        <button type="submit">Invia</button>
    </form>
</body>
</html>

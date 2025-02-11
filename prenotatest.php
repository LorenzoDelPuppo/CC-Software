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
   Classe WeekDay (per PHP <8.1, sostituisce l'enum)
   ====================== */
class WeekDay {
    public const MONDAY    = 1;
    public const TUESDAY   = 2;
    public const WEDNESDAY = 3;
    public const THURSDAY  = 4;
    public const FRIDAY    = 5;
    public const SATURDAY  = 6;
    public const SUNDAY    = 7;
    
    // Restituisce gli slot di appuntamento in base al giorno:
    // - Lunedì e Domenica: array vuoto
    // - Sabato: slot dalle 08:00 alle 17:00 (intervallo 15 minuti)
    // - Martedì-Venerdì: due fasce (08:30-12:30 e 15:00-19:00)
    public static function getSlots(int $day): array {
        if ($day === self::MONDAY || $day === self::SUNDAY) {
            return [];
        }
        if ($day === self::SATURDAY) {
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

// La logica distingue due step tramite il campo "action":
// - 'view' per mostrare il dropdown degli orari (senza inviare dati)
// - 'submit' per inviare la prenotazione (quando data, servizi e orario sono selezionati)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST['action'] ?? '';
    if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])) {
        $appointment_date_only = $_POST['appointment_date']; // formato "YYYY-MM-DD"
        
        if ($action === 'view') {
            // L'utente vuole solo visualizzare gli orari; non esegue azioni sul DB
        } else if ($action === 'submit') {
            // Se è stato selezionato anche un orario, invia i dati
            if (!isset($_POST['time_slot']) || empty($_POST['time_slot'])) {
                $message .= "Devi selezionare l'orario dell'appuntamento.<br>";
            } else {
                $time_slot = $_POST['time_slot']; // formato "HH:MM"
                // Combina la data e l'orario; il formato finale è "YYYY-MM-DD HH:MM:SS"
                $appointment_datetime = $appointment_date_only . " " . $time_slot . ":00";
                
                // Inserimento nella tabella appointment (non modificare se non strettamente necessario)
                $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("is", $customer_id, $appointment_datetime);
                    if ($stmt->execute()) {
                        $appointment_id = $stmt->insert_id;
                        $message .= "Appuntamento prenotato con successo!<br>";
                        
                        // Inserimento dei servizi selezionati per l'appuntamento in servicesOfAppointment
                        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
                            $sql2 = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                            if ($stmt2 = $conn->prepare($sql2)) {
                                foreach ($_POST['checkboxes'] as $service_id) {
                                    $service_id = intval($service_id);
                                    $sPera = ""; // Valore di default per sPera
                                    $stmt2->bind_param("iis", $appointment_id, $service_id, $sPera);
                                    $stmt2->execute();
                                }
                                $stmt2->close();
                            } else {
                                $message .= "Errore nella preparazione della query per servicesOfAppointment.<br>";
                            }
                        } else {
                            $message .= "Nessun servizio selezionato per servicesOfAppointment.<br>";
                        }
                        
                        // Inserimento dei servizi nella tabella mergeAS
                        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
                            $sql3 = "INSERT INTO mergeAS (appointment_id, service_id) VALUES (?, ?)";
                            if ($stmt3 = $conn->prepare($sql3)) {
                                foreach ($_POST['checkboxes'] as $service_id) {
                                    $service_id = intval($service_id);
                                    $stmt3->bind_param("ii", $appointment_id, $service_id);
                                    $stmt3->execute();
                                }
                                $stmt3->close();
                            } else {
                                $message .= "Errore nella preparazione della query per mergeAS.<br>";
                            }
                        } else {
                            $message .= "Nessun servizio selezionato per mergeAS.<br>";
                        }
                        
                    } else {
                        $message .= "Errore durante l'inserimento dell'appuntamento: " . $stmt->error . "<br>";
                    }
                    $stmt->close();
                } else {
                    $message .= "Errore nella preparazione della query per appointment.<br>";
                }
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
    // Funzione per gestire le relazioni obbligatorie/incompatibili per le checkbox
    function updateCheckboxStates(checkbox) {
      const selectedValue = parseInt(checkbox.value);
      const mandatoryRelations = { 2: [1], 3: [1], 4: [1], 5: [1], 7: [1], 8: [1], 9: [1] };
      const incompatibleRelations = { 3: [7], 4: [7,8], 5: [6,7], 6: [5,8], 7: [3,4,5,6], 8: [3,4,6] };
      if (checkbox.checked && mandatoryRelations[selectedValue]) {
        mandatoryRelations[selectedValue].forEach(value => {
          const relatedCheckbox = document.querySelector(`input[value="${value}"]`);
          if (relatedCheckbox) {
            relatedCheckbox.checked = true;
          }
        });
      }
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

    // Abilita/disabilita il pulsante in base alla selezione di data e servizi
    function checkForm() {
      var dateVal = $("#appointment_date").val();
      var serviceCount = $("input[name='checkboxes[]']:checked").length;
      if(dateVal !== "" && serviceCount > 0) {
        $("#submitButton").prop("disabled", false);
      } else {
        $("#submitButton").prop("disabled", true);
      }
    }

    $(document).ready(function(){
      $("#appointment_date").datepicker({
        dateFormat: 'yy-mm-dd'
      });
      $("input[name='checkboxes[]']").on("change", checkForm);
      $("#appointment_date").on("change", checkForm);
      checkForm();

      // Se il dropdown degli orari è presente, aggiorna dinamicamente il pulsante al cambio selezione
      $(document).on("change", "#time_slot", function(){
          var selectedTime = $(this).val();
          if(selectedTime !== ""){
              $("#submitButton").text("Invia");
              $("#submitButton").val("submit");
          } else {
              $("#submitButton").text("Visualizza orari disponibili");
              $("#submitButton").val("view");
          }
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
      <input type="checkbox" name="checkboxes[]" value="1" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('1', $_POST['checkboxes'])) echo "checked"; ?>> Piega<br>
      <input type="checkbox" name="checkboxes[]" value="2" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('2', $_POST['checkboxes'])) echo "checked"; ?>> Taglio<br>
      <input type="checkbox" name="checkboxes[]" value="3" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('3', $_POST['checkboxes'])) echo "checked"; ?>> Colore<br>
      <input type="checkbox" name="checkboxes[]" value="4" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('4', $_POST['checkboxes'])) echo "checked"; ?>> Mèche/Schiariture<br>
      <input type="checkbox" name="checkboxes[]" value="5" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('5', $_POST['checkboxes'])) echo "checked"; ?>> Permanente<br>
      <input type="checkbox" name="checkboxes[]" value="6" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('6', $_POST['checkboxes'])) echo "checked"; ?>> Stiratura<br>
      <input type="checkbox" name="checkboxes[]" value="7" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('7', $_POST['checkboxes'])) echo "checked"; ?>> Keratina<br>
      <input type="checkbox" name="checkboxes[]" value="8" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('8', $_POST['checkboxes'])) echo "checked"; ?>> Colore - Mèche<br>
      <input type="checkbox" name="checkboxes[]" value="9" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('9', $_POST['checkboxes'])) echo "checked"; ?>> Ricostruzione<br>
      <input type="checkbox" name="checkboxes[]" value="10" onchange="updateCheckboxStates(this)" <?php if(isset($_POST['checkboxes']) && in_array('10', $_POST['checkboxes'])) echo "checked"; ?>> Trattamento<br>
    </fieldset>
    <fieldset>
      <legend>Seleziona la data dell'appuntamento</legend>
      <label for="appointment_date">Data Appuntamento:</label>
      <input type="text" id="appointment_date" name="appointment_date" autocomplete="off" value="<?= isset($_POST['appointment_date']) ? htmlspecialchars($_POST['appointment_date']) : '' ?>">
    </fieldset>
    <?php
      // Se sono stati selezionati data e almeno un servizio, mostra il dropdown degli orari
      if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date']) &&
          isset($_POST['checkboxes']) && !empty($_POST['checkboxes'])):
          $selectedDate = $_POST['appointment_date'];
          $timestamp = strtotime($selectedDate);
          if ($timestamp !== false) {
              $dayNumber = (int)date('N', $timestamp);
              $availableSlots = WeekDay::getSlots($dayNumber);
              if (!empty($availableSlots)) {
                  echo '<fieldset>';
                  echo '<legend>Seleziona l\'orario dell\'appuntamento</legend>';
                  echo '<label for="time_slot">Orario:</label>';
                  echo '<select name="time_slot" id="time_slot">';
                  echo '<option value="">-- Seleziona un orario --</option>';
                  foreach ($availableSlots as $slot) {
                      $sel = (isset($_POST['time_slot']) && $_POST['time_slot'] == $slot) ? ' selected' : '';
                      echo "<option value='" . htmlspecialchars($slot) . "'$sel>" . htmlspecialchars($slot) . "</option>";
                  }
                  echo '</select>';
                  echo '</fieldset>';
              } else {
                  echo "<p>Nessun appuntamento disponibile per il giorno selezionato.</p>";
              }
          }
      endif;
    ?>
    <!-- Il pulsante ha id="submitButton" e il suo testo/valore viene aggiornato dinamicamente -->
    <button type="submit" name="action" value="<?php echo (isset($_POST['time_slot']) && !empty($_POST['time_slot'])) ? 'submit' : 'view'; ?>" id="submitButton">
      <?php echo (isset($_POST['time_slot']) && !empty($_POST['time_slot'])) ? 'Invia' : 'Visualizza orari disponibili'; ?>
    </button>
  </form>
</body>
</html>
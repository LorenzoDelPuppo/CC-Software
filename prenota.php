<?php  

session_start();
require_once 'connect.php'; // Includi il file di connessione al DB

// === Funzioni Helper ===
function timeToMinutes(string $timeStr): int {
    [$hours, $minutes] = explode(":", $timeStr);
    return ((int)$hours * 60) + (int)$minutes;
}

function minutesToTime(int $minutes): string {
    $hours = floor($minutes / 60);
    $mins  = $minutes % 60;
    return sprintf("%02d:%02d", $hours, $mins);
}

// === Verifica se l'utente è loggato ===
if (!isset($_SESSION['email'])) {
  header("Location: login.php");
}

// Se il customer_id non è in sessione, lo recupero dal DB
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

// === Gestione del form (POST) ===
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])) {
        $appointment_date_only = $_POST['appointment_date']; // formato "YYYY-MM-DD"
        
        // Calcolo la durata totale dei servizi selezionati (server-side)
        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes']) && count($_POST['checkboxes']) > 0) {
            $ids = array_map('intval', $_POST['checkboxes']);
            $in = implode(',', $ids);
            $queryDur = "SELECT SUM(timeTOT) as total FROM serviceCC WHERE service_id IN ($in)";
            $resultDur = $conn->query($queryDur);
            if ($resultDur && $rowDur = $resultDur->fetch_assoc()) {
                $requiredDuration = intval($rowDur['total']);
            } else {
                $requiredDuration = 0;
            }
        } else {
            $requiredDuration = 0;
        }
        
        if ($requiredDuration <= 0) {
            $message .= "Errore: seleziona almeno un servizio valido.<br>";
        }
        
        if (isset($_POST['time_slot']) && !empty($_POST['time_slot'])) {
            $time_slot = $_POST['time_slot']; // formato "HH:MM"
            
            // --- Controllo di disponibilità lato server ---
            $slotStart = timeToMinutes($time_slot);
            $slotEnd   = $slotStart + $requiredDuration;
            
            // Determino l'orario di chiusura della sessione in base al giorno
            $dayNumber = (int)date('N', strtotime($appointment_date_only));
            if ($dayNumber === 6) { // Sabato
                $sessionClosing = timeToMinutes("17:00");
            } else {
                // Martedì - Venerdì (lunedì e domenica non sono disponibili)
                if ($slotStart < timeToMinutes("12:30")) {
                    $sessionClosing = timeToMinutes("12:30");
                } else {
                    $sessionClosing = timeToMinutes("19:00");
                }
            }
            // Se l'appuntamento terminerebbe dopo il termine della sessione, blocca la prenotazione
            if ($slotEnd > $sessionClosing) {
                $message .= "Errore: l'appuntamento terminerebbe oltre l'orario di chiusura della sessione.<br>";
            }
            
            // Controllo le sovrapposizioni (max 2 appuntamenti per lo stesso intervallo)
            $sql = "SELECT a.dateTime, SUM(sc.timeTOT) as duration 
                    FROM appointment a 
                    JOIN mergeAS mas ON a.appointment_id = mas.appointment_id
                    JOIN serviceCC sc ON mas.service_id = sc.service_id
                    WHERE DATE(a.dateTime) = ?
                    GROUP BY a.appointment_id";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $appointment_date_only);
                $stmt->execute();
                $result = $stmt->get_result();
                $overlapCount = 0;
                while ($row = $result->fetch_assoc()) {
                    $apptStart = timeToMinutes(date("H:i", strtotime($row['dateTime'])));
                    $apptEnd   = $apptStart + intval($row['duration']);
                    if ($slotStart < $apptEnd && $slotEnd > $apptStart) {
                        $overlapCount++;
                    }
                }
                $stmt->close();
                if ($overlapCount >= 2) {
                    $message .= "Errore: non ci sono posti disponibili in questo orario.<br>";
                }
            } else {
                $message .= "Errore nella preparazione della query per verificare la disponibilità.<br>";
            }
            
            // --- Inserimento dell'appuntamento (solo se non sono stati rilevati errori) ---
            if (empty($message)) {
                $appointment_datetime = $appointment_date_only . " " . $time_slot . ":00";
                $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("is", $customer_id, $appointment_datetime);
                    if ($stmt->execute()) {
                        $appointment_id = $stmt->insert_id;
                        $message .= "Appuntamento prenotato con successo!<br>";
                        
                        // Inserimento nella tabella servicesOfAppointment
                        if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
                            $sql2 = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                            if ($stmt2 = $conn->prepare($sql2)) {
                                foreach ($_POST['checkboxes'] as $service_id) {
                                    $service_id = intval($service_id);
                                    $sPera = "";
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
                        
                        // Inserimento nella tabella mergeAS
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
        } else {
            $message .= "Devi selezionare l'orario dell'appuntamento.<br>";
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
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href="menu.php">
      <img src="style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href="profilo.php" class="dropdown-item">Profilo</a>
    <a href="settings.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href="logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>

</div>

  </div>
</div>


  <title>Prenotazione Appuntamenti</title>
  <!-- Includo jQuery e jQuery UI per il datepicker -->
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

  <!-- Link al file CSS esterno -->
  <link rel="stylesheet" href="style/style_prenota.css">
  <script src="menu_profilo.js" defer></script>
  <script>

    // Mappa delle durate (in minuti) per ciascun servizio (AGGIORNATA)
    var serviceDurations = {
      1: 55,    // Piega
      2: 45,    // Taglio
      3: 70,    // Colore
      4: 100,   // Mèche - Schiariture
      5: 70,    // Permanente
      6: 70,    // Stiratura
      7: 135,   // Keratina
      8: 125,   // Colori - Mèche
      9: 30,    // Ricostruzione
      10: 25    // Trattamento
    };

    // Calcola la durata totale in base ai servizi selezionati
    function calcolaDurataTotale() {
      var total = 0;
      $("input[name='checkboxes[]']:checked").each(function(){
        var id = $(this).val();
        total += serviceDurations[id] || 0;
      });
      return total;
    }

    // Aggiorna il dropdown degli orari disponibili tramite AJAX (chiamata a get_slots.php)
    function updateAvailableSlots() {
      var dateVal = $("#appointment_date").val();
      if(dateVal === "") {
        return;
      }
      var totalDuration = calcolaDurataTotale();
      if(totalDuration === 0) {
        return;
      }
      $.ajax({
        url: 'get_slots.php',
        method: 'GET',
        data: { date: dateVal, duration: totalDuration },
        dataType: 'json',
        success: function(response) {
          $("#time_slot").empty();
          if(response.error) {
            $("#time_slot").append($('<option>', { value: '', text: response.error }));
          } else if(response.length === 0) {
            $("#time_slot").append($('<option>', { value: '', text: 'Nessun orario disponibile' }));
          } else {
            $("#time_slot").append($('<option>', { value: '', text: '-- Seleziona un orario --' }));
            $.each(response, function(index, time) {
              $("#time_slot").append($('<option>', { value: time, text: time }));
            });
          }
        },
        error: function() {
          alert("Errore nel recupero degli orari disponibili.");
        }
      });
    }

    // Funzione per gestire relazioni obbligatorie/incompatibili fra checkbox (personalizzabile)
    function updateCheckboxStates(checkbox) {
      const selectedValue = parseInt(checkbox.value);
      const mandatoryRelations = { 2: [1], 3: [1], 4: [1], 5: [1], 7: [1], 8: [1], 9: [1] };
      const incompatibleRelations = { 3: [7], 4: [7,8], 5: [6,7], 6: [5,8], 7: [3,4,5,6], 8: [3,4,6] };
      if (checkbox.checked && mandatoryRelations[selectedValue]) {
        mandatoryRelations[selectedValue].forEach(function(value) {
          var relatedCheckbox = document.querySelector('input[value="'+value+'"]');
          if (relatedCheckbox) {
            relatedCheckbox.checked = true;
          }
        });
      }
      document.querySelectorAll('input[type="checkbox"]').forEach(function(cb) {
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
      $("input[name='checkboxes[]']").on("change", function(){
        checkForm();
        updateAvailableSlots();
      });
      $("#appointment_date").on("change", function(){
        checkForm();
        updateAvailableSlots();
      });
      checkForm();
      $(document).on("change", "#time_slot", function(){
          var selectedTime = $(this).val();
          if(selectedTime !== ""){
              $("#submitButton").text("prenota");
          } else {
              $("#submitButton").text("prenota");
          }
      });
    });
  </script>
</head>
<body>
  
<div class="prenota-container">
<div class="container">
  <h1>Prenota il tuo Appuntamento</h1>
  <?php
    if (!empty($message)) {
      echo "<p>$message</p>";
    }
  ?>
  <form method="post" action="prenota.php">
    <fieldset>
      <legend>Seleziona i servizi</legend>
      <div class="services-container">
        <!-- Ogni servizio racchiuso in label + span per stile "pill" -->
        <label>
          <input type="checkbox" name="checkboxes[]" value="1" onchange="updateCheckboxStates(this)">
          <span>Piega</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="2" onchange="updateCheckboxStates(this)">
          <span>Taglio</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="3" onchange="updateCheckboxStates(this)">
          <span>Colore</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="4" onchange="updateCheckboxStates(this)">
          <span>Mèche/Schiariture</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="5" onchange="updateCheckboxStates(this)">
          <span>Permanente</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="6" onchange="updateCheckboxStates(this)">
          <span>Stiratura</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="7" onchange="updateCheckboxStates(this)">
          <span>Keratina</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="8" onchange="updateCheckboxStates(this)">
          <span>Colori - Mèche</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="9" onchange="updateCheckboxStates(this)">
          <span>Ricostruzione</span>
        </label>
        <label>
          <input type="checkbox" name="checkboxes[]" value="10" onchange="updateCheckboxStates(this)">
          <span>Trattamento</span>
        </label>
      </div>
    </fieldset>

    <fieldset>
      <legend>Seleziona la data dell'appuntamento</legend>
      <label for="appointment_date">Data Appuntamento:</label>
      <input type="text" id="appointment_date" name="appointment_date" autocomplete="off">
    </fieldset>

    <fieldset>
      <legend>Seleziona l'orario dell'appuntamento</legend>
      <label for="time_slot">Orario:</label>
      <select name="time_slot" id="time_slot">
        <option value="">-- Seleziona un orario --</option>
      </select>
    </fieldset>

    <button type="submit" id="submitButton" disabled>Visualizza orari disponibili</button>
  </form>
  </div>
</div>
</body>
</html>

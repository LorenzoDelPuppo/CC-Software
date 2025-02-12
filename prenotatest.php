<?php  
session_start();
require_once 'connect.php'; // Includi il file di connessione al DB

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    die("Errore: Devi effettuare il login per prenotare un appuntamento.");
}

// Se il customer_id non è già in sessione, recuperalo dal DB usando l'email
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

// Se il form viene inviato, gestisco la prenotazione
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['appointment_date']) && !empty($_POST['appointment_date'])) {
        $appointment_date_only = $_POST['appointment_date']; // formato "YYYY-MM-DD"
        
        if (isset($_POST['time_slot']) && !empty($_POST['time_slot'])) {
            $time_slot = $_POST['time_slot']; // formato "HH:MM"
            // Combino data e orario (formato "YYYY-MM-DD HH:MM:SS")
            $appointment_datetime = $appointment_date_only . " " . $time_slot . ":00";
            
            // Inserimento nella tabella appointment
            $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("is", $customer_id, $appointment_datetime);
                if ($stmt->execute()) {
                    $appointment_id = $stmt->insert_id;
                    $message .= "Appuntamento prenotato con successo!<br>";
                    
                    // Inserimento dei servizi selezionati in servicesOfAppointment
                    if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes'])) {
                        $sql2 = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                        if ($stmt2 = $conn->prepare($sql2)) {
                            foreach ($_POST['checkboxes'] as $service_id) {
                                $service_id = intval($service_id);
                                $sPera = ""; // valore di default
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Prenotazione Appuntamenti</title>
  <!-- Includo jQuery e jQuery UI per il datepicker -->
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <script>
    // Mappa delle durate dei servizi (service_id => durata in minuti)
    var serviceDurations = {
      1: 55,
      2: 85,
      3: 115,
      4: 145,
      5: 125,
      6: 80,
      7: 210,
      8: 205,
      9: 80,
      10: 25
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

    // Funzione per aggiornare gli orari disponibili tramite chiamata AJAX a get_slots.php
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

    // Funzione per gestire checkbox con relazioni obbligatorie/incompatibili (personalizzabili)
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

    // Abilita/disabilita il pulsante in base alla selezione della data e dei servizi
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

      // Aggiornamento dinamico del pulsante in base alla selezione dell'orario
      $(document).on("change", "#time_slot", function(){
          var selectedTime = $(this).val();
          if(selectedTime !== ""){
              $("#submitButton").text("Invia");
          } else {
              $("#submitButton").text("Visualizza orari disponibili");
          }
      });
    });
  </script>
</head>
<body>
  <h1>Prenotazione Appuntamenti</h1>
  <?php
    if (!empty($message)) {
      echo "<p>$message</p>";
    }
  ?>
  <form method="post" action="prenotatest.php">
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
</body>
</html>

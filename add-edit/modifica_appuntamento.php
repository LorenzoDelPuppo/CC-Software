<?php 
ob_start();
session_start();
require_once __DIR__ . '/../connect.php';
require_once '.././add-edit/cript.php';

// Blocco di controllo accessi: solo amministratore e operatrice possono accedere
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit;
}
$email = $_SESSION['email'];
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
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}

// Verifica che venga passato un appointment_id tramite GET
$appointment_id = $_GET['appointment_id'] ?? null;
if (!$appointment_id) {
    die("Nessun appuntamento selezionato.");
}

$success = "";
$error = "";

// Recupera i dettagli dell'appuntamento
$sqlAppt = "SELECT dateTime FROM appointment WHERE appointment_id = ?";
$stmt = $conn->prepare($sqlAppt);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$stmt->bind_result($dateTime);
$stmt->fetch();
$stmt->close();
if (!$dateTime) {
    die("Appuntamento non trovato.");
}
$currentDate = date("Y-m-d", strtotime($dateTime));
$currentTime = date("H:i", strtotime($dateTime));

// Recupera i servizi associati all'appuntamento
$selectedServiceIds = [];
$sqlServices = "SELECT service_id FROM mergeAS WHERE appointment_id = ?";
$stmt = $conn->prepare($sqlServices);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $selectedServiceIds[] = $row['service_id'];
}
$stmt->close();

// Recupera tutti i servizi disponibili
$allServices = [];
$sqlAll = "SELECT service_id, nameS FROM serviceCC";
$result = $conn->query($sqlAll);
while ($row = $result->fetch_assoc()) {
    $allServices[] = $row;
}

// Gestione del form per aggiornare l'appuntamento
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $newDate = $_POST['appointment_date'] ?? "";
    $newTime = $_POST['time_slot'] ?? "";
    $selectedServices = $_POST['checkboxes'] ?? [];
    
    if (empty($newDate) || empty($newTime)) {
        $error = "Data e orario sono obbligatori.";
    } else {
        $newDateTime = $newDate . " " . $newTime . ":00";
        
        // Aggiorna l'appuntamento
        $sqlUpdate = "UPDATE appointment SET dateTime = ? WHERE appointment_id = ?";
        if ($stmt = $conn->prepare($sqlUpdate)) {
            $stmt->bind_param("si", $newDateTime, $appointment_id);
            if ($stmt->execute()) {
                // Elimina i servizi esistenti
                $sqlDeleteMerge = "DELETE FROM mergeAS WHERE appointment_id = ?";
                $stmtDel = $conn->prepare($sqlDeleteMerge);
                $stmtDel->bind_param("i", $appointment_id);
                $stmtDel->execute();
                $stmtDel->close();
                
                $sqlDeleteServ = "DELETE FROM servicesOfAppointment WHERE appointment_id = ?";
                $stmtDel2 = $conn->prepare($sqlDeleteServ);
                $stmtDel2->bind_param("i", $appointment_id);
                $stmtDel2->execute();
                $stmtDel2->close();
                
                // Inserisci i nuovi servizi, se selezionati
                if (!empty($selectedServices)) {
                    foreach ($selectedServices as $service_id) {
                        $service_id = intval($service_id);
                        // Inserisci in mergeAS
                        $sqlInsertMerge = "INSERT INTO mergeAS (appointment_id, service_id) VALUES (?, ?)";
                        $stmtInsertMerge = $conn->prepare($sqlInsertMerge);
                        $stmtInsertMerge->bind_param("ii", $appointment_id, $service_id);
                        $stmtInsertMerge->execute();
                        $stmtInsertMerge->close();
                        
                        // Inserisci in servicesOfAppointment (con sPera vuoto)
                        $sPera = "";
                        $sqlInsertServ = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                        $stmtInsertServ = $conn->prepare($sqlInsertServ);
                        $stmtInsertServ->bind_param("iis", $appointment_id, $service_id, $sPera);
                        $stmtInsertServ->execute();
                        $stmtInsertServ->close();
                    }
                }
                $success = "Appuntamento aggiornato con successo!";
                // Aggiorna i valori correnti per il form
                $currentDate = $newDate;
                $currentTime = $newTime;
                $selectedServiceIds = $selectedServices;
            } else {
                $error = "Errore durante l'aggiornamento: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Errore nella preparazione della query di aggiornamento.";
        }
    }
}
$conn->close();
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Modifica Appuntamento</title>
  <link rel="stylesheet" href="style/prenota.css">
  <script src=".././js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href=".././style/barra_alta.css">
  <!-- Includo jQuery e jQuery UI per il Datepicker -->
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
  <div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href=".././view-get/menu.php">
      <img src="style/rullino/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href=".././view-get/profilo.php" class="dropdown-item">Profilo</a>
    <a href=".././add-edit/impostazioni.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href=".././add-edit/logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>

</div>
  <script>
    // Mappa delle durate (in minuti) per ciascun servizio (stessa usata in prenota)
    var serviceDurations = {
      1: 55,    // Piega
      2: 45,    // Taglio
      3: 70,    // Colore
      4: 100,   // Mèche/Schiariture
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

    // Aggiorna il dropdown degli orari disponibili tramite AJAX (simile a prenota)
    function updateAvailableSlots() {
      var dateVal = $("#appointment_date").val();
      if(dateVal === ""){
        return;
      }
      var totalDuration = calcolaDurataTotale();
      if(totalDuration === 0){
        return;
      }
      $.ajax({
        url: '.././view-get/get_slots.php',
        method: 'GET',
        data: { date: dateVal, duration: totalDuration },
        dataType: 'json',
        success: function(response){
          $("#time_slot").empty();
          if(response.error){
            $("#time_slot").append($('<option>', { value: '', text: response.error }));
          } else if(response.length === 0){
            $("#time_slot").append($('<option>', { value: '', text: 'Nessun orario disponibile' }));
          } else {
            $("#time_slot").append($('<option>', { value: '', text: '-- Seleziona un orario --' }));
            $.each(response, function(index, time){
                $("#time_slot").append($('<option>', { value: time, text: time }));
            });
            // Preseleziona l'orario corrente se disponibile
            var currentTime = "<?php echo $currentTime; ?>";
            $("#time_slot option").each(function(){
                if($(this).val() == currentTime){
                    $(this).prop('selected', true);
                }
            });
          }
        },
        error: function(){
          alert("Errore nel recupero degli orari disponibili.");
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
    });
  </script>
</head>
<body>
<div class="form-container">
  <h1>Modifica Appuntamento</h1>
  <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
  <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
  
  <form method="post" action=".././add-edit/modifica_appuntamento.php?appointment_id=<?php echo $appointment_id; ?>">
    <fieldset>
      <legend>Seleziona i Servizi</legend>
      <div class="services-container">
        <?php foreach ($allServices as $service): ?>
          <label>
            <input type="checkbox" name="checkboxes[]" value="<?php echo $service['service_id']; ?>"
              <?php echo in_array($service['service_id'], $selectedServiceIds) ? "checked" : ""; ?>>
            <?php echo htmlspecialchars($service['nameS']); ?>
          </label>
        <?php endforeach; ?>
      </div>
    </fieldset>

    <fieldset>
      <legend>Seleziona la Data dell'Appuntamento</legend>
      <label for="appointment_date">Data:</label>
      <input type="text" id="appointment_date" name="appointment_date" value="<?php echo $currentDate; ?>" autocomplete="off" required>
    </fieldset>

    <fieldset>
      <legend>Seleziona l'Orario dell'Appuntamento</legend>
      <label for="time_slot">Orario:</label>
      <select name="time_slot" id="time_slot" required>
        <option value="">-- Seleziona un orario --</option>
      </select>
    </fieldset>

    <div class="button-container">
      <button type="submit" id="submitButton" disabled>Aggiorna Appuntamento</button>
    </div>
  </form>
</div>
</body>
</html>

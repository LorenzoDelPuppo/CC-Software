<?php /*
session_start();
require_once __DIR__ . '/../connect.php';
require_once '.././add-edit/cript.php';

// Controllo accessi: solo amministratore e operatrice
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit;
}
$emailSession = $_SESSION['email'];
$sql = "SELECT user_tipe FROM Customer WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $emailSession);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}

// Inizializzo variabili per messaggi e utente selezionato
$utenteSelezionato = null;
$messaggioUtente = "";
$messaggioPrenotazione = "";

// Gestione ricerca utente
if (isset($_POST['ricercaUtente'])) {
    $ricerca = trim($_POST['ricerca']);
    $ricercaLike = "%" . $ricerca . "%";
    $sql = "SELECT customer_id, fName, lName, email FROM Customer 
            WHERE fName LIKE ? OR lName LIKE ? OR CONCAT(fName, ' ', lName) LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $ricercaLike, $ricercaLike, $ricercaLike, $ricercaLike);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        // Prendo il primo risultato trovato
        $utenteSelezionato = $result->fetch_assoc();
    } else {
        $messaggioUtente = "Nessun utente trovato.";
    }
    $stmt->close();
}

// Gestione registrazione utente (simile al form di registrazione del sito)
if (isset($_POST['aggiungiUtente'])) {
    $firstName   = trim($_POST['fName']);
    $lastName    = trim($_POST['sName']);
    $phoneNumber = trim($_POST['phoneN']);
    $emailNew    = trim($_POST['email_nuovo']);
    $password    = $_POST['password'];
    $hairType    = $_POST['lunghezzaCapelli'];
    $gender      = $_POST['gender'];
    $userTypeNew = $_POST['user_tipe'];
    
    if(empty($firstName) || empty($lastName) || empty($emailNew) || empty($password)) {
        $messaggioUtente = "Compila tutti i campi obbligatori.";
    } else {
        // Controllo che l'email non sia già registrata
        $sqlCheck = "SELECT customer_id FROM Customer WHERE email = ?";
        if($stmtCheck = $conn->prepare($sqlCheck)) {
            $stmtCheck->bind_param("s", $emailNew);
            $stmtCheck->execute();
            $stmtCheck->store_result();
            if($stmtCheck->num_rows > 0) {
                $messaggioUtente = "Un utente con questa email esiste già.";
                $stmtCheck->close();
            } else {
                $stmtCheck->close();
                $hashedPassword = hashPassword($password);
                $sqlInsert = "INSERT INTO Customer (fName, lName, hair, phoneN, password, gender, preference, email, user_tipe)
                              VALUES (?, ?, ?, ?, ?, ?, 'Casuale', ?, ?)";
                if ($stmt = $conn->prepare($sqlInsert)) {
                    $stmt->bind_param("ssssssss", $firstName, $lastName, $hairType, $phoneNumber, $hashedPassword, $gender, $emailNew, $userTypeNew);
                    if ($stmt->execute()) {
                        $messaggioUtente = "Utente registrato con successo!";
                        $utenteSelezionato = [
                            'customer_id' => $stmt->insert_id,
                            'fName'       => $firstName,
                            'lName'       => $lastName,
                            'email'       => $emailNew
                        ];
                    } else {
                        $messaggioUtente = "Errore durante la registrazione: " . $stmt->error;
                    }
                    $stmt->close();
                } else {
                    $messaggioUtente = "Errore nella preparazione della query: " . $conn->error;
                }
            }
        }
    }
}

// Helper: converte un orario (HH:MM) in minuti
function timeToMinutes(string $timeStr): int {
    list($h, $m) = explode(":", $timeStr);
    return ((int)$h * 60) + (int)$m;
}

// Gestione prenotazione appuntamento
if (isset($_POST['prenotaAppuntamento'])) {
    if (!isset($_POST['cliente_id']) || empty($_POST['cliente_id'])) {
        $messaggioPrenotazione = "Per prenotare l'appuntamento devi prima cercare o registrare un utente.";
    } else {
        $cliente_id = intval($_POST['cliente_id']);
        $appointment_date = $_POST['appointment_date'];
        $time_slot = $_POST['time_slot'];
        $servizi = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : [];
        
        // Calcolo la durata totale dei servizi selezionati
        $requiredDuration = 0;
        $serviceDurations = [1 => 55, 2 => 45, 3 => 70, 4 => 100, 5 => 70, 6 => 70, 7 => 135, 8 => 125, 9 => 30, 10 => 25];
        foreach ($servizi as $s) {
            $requiredDuration += isset($serviceDurations[$s]) ? $serviceDurations[$s] : 0;
        }
        
        if ($requiredDuration <= 0) {
            $messaggioPrenotazione .= "Seleziona almeno un servizio valido.<br>";
        }
        
        if (!empty($time_slot)) {
            $slotStart = timeToMinutes($time_slot);
            $slotEnd   = $slotStart + $requiredDuration;
            // Esempio: se sabato, chiusura alle 17:00, altrimenti in base all'orario di inizio
            $dayNumber = (int)date('N', strtotime($appointment_date));
            $sessionClosing = ($dayNumber === 6) ? timeToMinutes("17:00") : (($slotStart < timeToMinutes("12:30")) ? timeToMinutes("12:30") : timeToMinutes("19:00"));
            
            if ($slotEnd > $sessionClosing) {
                $messaggioPrenotazione .= "Errore: l'appuntamento terminerebbe oltre l'orario di chiusura.<br>";
            }
            
            if (empty($messaggioPrenotazione)) {
                $appointment_datetime = $appointment_date . " " . $time_slot . ":00";
                $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("is", $cliente_id, $appointment_datetime);
                    if ($stmt->execute()) {
                        $appointment_id = $stmt->insert_id;
                        $messaggioPrenotazione .= "Appuntamento prenotato con successo!<br>";
                        
                        // Inserimento dei servizi
                        $sql2 = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
                        if ($stmt2 = $conn->prepare($sql2)) {
                            foreach ($servizi as $service_id) {
                                $sPera = "";
                                $stmt2->bind_param("iis", $appointment_id, $service_id, $sPera);
                                $stmt2->execute();
                            }
                            $stmt2->close();
                        }
                    } else {
                        $messaggioPrenotazione .= "Errore nella prenotazione: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } else {
            $messaggioPrenotazione .= "Seleziona l'orario dell'appuntamento.<br>";
        }
    }
}*/
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prenotazione Appuntamento - Operatrice</title>
    <link rel="stylesheet" href=".././style/style_input.css">
    <link rel="stylesheet" href=".././style/style_prenota.css">
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <style>
        /* Layout a due colonne */
        .flex-container {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .col {
            width: 48%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
        }
        .col h2 { margin-top: 0; }
        /* Esempio di stile per le immagini dei capelli (puoi sostituire i src con i percorsi corretti) */
        .hair-option {
            display: inline-block;
            text-align: center;
            margin-right: 10px;
        }
        .hair-option input { display: block; margin: 0 auto; }
        .hair-option img { width: 50px; height: auto; }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function(){
            $("#appointment_date").datepicker({ dateFormat: 'yy-mm-dd' });

            // Funzione per calcolare la durata totale dei servizi selezionati
            function calcolaDurataTotale() {
                var serviceDurations = {1:55, 2:45, 3:70, 4:100, 5:70, 6:70, 7:135, 8:125, 9:30, 10:25};
                var total = 0;
                $("input[name='checkboxes[]']:checked").each(function(){
                    var id = $(this).val();
                    total += serviceDurations[id] || 0;
                });
                return total;
            }

            // Funzione per aggiornare gli orari disponibili tramite AJAX
            function updateAvailableSlots() {
                var dateVal = $("#appointment_date").val();
                var totalDuration = calcolaDurataTotale();
                if(dateVal === "" || totalDuration === 0) return;
                $.ajax({
                    url: '.././view-get/get_slots.php',
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

            // Aggiorna gli orari al cambio della data o dei servizi
            $("#appointment_date").on("change", updateAvailableSlots);
            $("input[name='checkboxes[]']").on("change", updateAvailableSlots);
        });
    </script>
</head>
<body>
<div class="container">
    <h1>Prenotazione Appuntamento - Operatrice</h1>
    <div class="flex-container">
        <!-- Colonna Sinistra: Ricerca/Registrazione Utente -->
        <div class="col" id="col-user">
            <h2>Ricerca Utente</h2>
            <form method="post" action="">
                <input type="text" name="ricerca" placeholder="Inserisci nome, cognome o email">
                <button type="submit" name="ricercaUtente">Cerca Utente</button>
            </form>
            <?php if (!empty($messaggioUtente)) : ?>
                <p><?php echo htmlspecialchars($messaggioUtente); ?></p>
            <?php endif; ?>
            <?php if ($utenteSelezionato): ?>
                <p>
                    Utente trovato: <?php echo htmlspecialchars($utenteSelezionato['fName'] . " " . $utenteSelezionato['lName']); ?><br>
                    Email: <?php echo htmlspecialchars($utenteSelezionato['email']); ?><br>
                    (ID: <?php echo $utenteSelezionato['customer_id']; ?>)
                </p>
            <?php else: ?>
                <h3>Registrazione Nuovo Utente</h3>
                <form method="post" action="">
                    <input type="text" name="fName" placeholder="Nome" required>
                    <input type="text" name="sName" placeholder="Cognome" required>
                    <input type="tel" name="phoneN" placeholder="Telefono">
                    <input type="email" name="email_nuovo" placeholder="Email" required>
                    <input type="password" name="password" placeholder="Password" required>
                    <div>
                        <label>Capelli:</label><br>
                        <!-- Esempio con immagini; sostituisci i src con i percorsi corretti -->
                        <div class="hair-option">
                            <input type="radio" name="lunghezzaCapelli" value="lunghi" required>
                            <img src=".././style/rullino/capelliLunghi.png" alt="Capelli Lunghi">
                            <span>Lunghi</span>
                        </div>
                        <div class="hair-option">
                            <input type="radio" name="lunghezzaCapelli" value="corti" required>
                            <img src=".././style/rullino/capellicorti.png" alt="Capelli Corti">
                            <span>Corti</span>
                        </div>
                    </div>
                    <br>
                    <label>Genere:</label>
                    <select name="gender" required>
                        <option value="maschio">Maschio</option>
                        <option value="femmina">Femmina</option>
                    </select>
                    <br>
                    <label>Tipo Utente:</label>
                    <select name="user_tipe" required>
                        <option value="cliente">Cliente</option>
                        <option value="amministratore">Amministratore</option>
                        <option value="operatrice">Operatrice</option>
                    </select>
                    <br>
                    <button type="submit" name="aggiungiUtente">Registra Utente</button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Colonna Destra: Prenotazione Appuntamento -->
        <div class="col" id="col-appointment">
            <h2>Prenota Appuntamento</h2>
            <?php if (!empty($messaggioPrenotazione)) : ?>
                <p><?php echo $messaggioPrenotazione; ?></p>
            <?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="cliente_id" value="<?php echo $utenteSelezionato ? $utenteSelezionato['customer_id'] : ''; ?>">
                <fieldset>
                    <legend>Seleziona i Servizi</legend>
                    <?php 
                    $serviceDurations = [1 => 55, 2 => 45, 3 => 70, 4 => 100, 5 => 70, 6 => 70, 7 => 135, 8 => 125, 9 => 30, 10 => 25];
                    foreach ($serviceDurations as $id => $durata): ?>
                        <label>
                            <input type="checkbox" name="checkboxes[]" value="<?php echo $id; ?>">
                            <span><?php echo "Servizio " . $id; ?></span>
                        </label>
                    <?php endforeach; ?>
                </fieldset>
                <fieldset>
                    <legend>Data Appuntamento</legend>
                    <label for="appointment_date">Data:</label>
                    <input type="text" id="appointment_date" name="appointment_date" autocomplete="off" required>
                </fieldset>
                <fieldset>
                    <legend>Orario Appuntamento</legend>
                    <label for="time_slot">Orario:</label>
                    <select id="time_slot" name="time_slot" required>
                        <option value="">-- Seleziona un orario --</option>
                    </select>
                </fieldset>
                <?php if ($utenteSelezionato): ?>
                    <button type="submit" name="prenotaAppuntamento">Prenota Appuntamento</button>
                <?php else: ?>
                    <p style="color:red;">Devi cercare o registrare un utente per prenotare.</p>
                    <button type="submit" name="prenotaAppuntamento" disabled>Prenota Appuntamento</button>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
</body>
</html>

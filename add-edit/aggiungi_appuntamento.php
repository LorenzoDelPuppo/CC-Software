<?php
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

// Inizializzo variabili
$utenteSelezionato = null;
$messaggioUtente = "";
$messaggioPrenotazione = "";
$utentiTrovati = []; // Array per più risultati ricerca

// Gestione selezione utente dalla lista
if (isset($_POST['selezionaUtente'])) {
    $idSelezionato = intval($_POST['selezionaUtente']);
    $sql = "SELECT customer_id, fName, lName, email FROM Customer WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $idSelezionato);
    $stmt->execute();
    $result = $stmt->get_result();
    $utenteSelezionato = $result->fetch_assoc();
    $stmt->close();
}

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
        // Recupera tutti gli utenti trovati
        while ($row = $result->fetch_assoc()) {
            $utentiTrovati[] = $row;
        }
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
                        } else {
                            die("Errore nella preparazione della query per servicesOfAppointment: " . $conn->error);
                        }

                        // Inserimento nella tabella mergeAS
                        $sql3 = "INSERT INTO mergeAS (appointment_id, service_id) VALUES (?, ?)";
                        if ($stmt3 = $conn->prepare($sql3)) {
                            foreach ($servizi as $service_id) {
                                $stmt3->bind_param("ii", $appointment_id, $service_id);
                                $stmt3->execute();
                            }
                            $stmt3->close();
                        } else {
                            die("Errore nella preparazione della query per mergeAS: " . $conn->error);
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
}
?>
    <!DOCTYPE html>
    <html lang="it">
    <link rel="stylesheet" href=".././style/barra_alta.css">
    <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
    <div class="top-bar">
    <script src=".././js/menu_profilo.js"></script>
    <div class="left-section">
        <a href=".././view-get/menu.php">
        <img src=".././style/rullino/tasto_home.svg" alt="Home" class="home-button">
        </a>
    </div>
    <div class="center-section">
        <a href=".././view-get/menu.php">
        <img src=".././style/rullino/logo.png" alt="Logo" class="logo" />
        </a>
    </div>

    <div class="right-section">
        <div class="user-menu">
        <!-- Icona utente (o un'immagine) -->
        <img src=".././style/rullino/fotodefault.png" alt="User Icon" class="user-icon">

        <!-- Dropdown -->
        <div class="dropdown-menu">
            <a href=".././view-get/profilo.php" class="dropdown-item">
            <img src=".././style/rullino/profilo.svg" alt="Profilo Icon" class="logout-icon">
            Profilo
            </a>
            <a href=".././add-edit/impostazioni.php" class="dropdown-item">
            <img src=".././style/rullino/imp.svg" alt="Impostazioni Icon" class="logout-icon">
            Impostazioni
            </a>
            <br>
            <hr class="dropdown-separator">
            
            <!-- Logout con icona e testo sulla stessa riga -->
            <a href=".././add-edit/logout.php" class="dropdown-item logout-item">
            <img src=".././style/rullino/logoutr.svg" alt="Logout Icon" class="logout-icon">
            Logout
            </a>
        </div>
        </div>
    </div>
    </div>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prenotazione Appuntamento - Operatrice</title>
    <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
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

        .img_sceltacapelli {
            width: 75px;
            height: 75px;
            border-radius: 50%;
            cursor: pointer;
            transition: border 0.3s ease, transform 0.3s ease;
        }

        /* Bottone in stato hover */
        .form-container button:hover {
            background-color: #333;
        }

        .form-container {
            width: 350px;
            padding: 25px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.4);
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-container input,
        .form-container select  {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            background-color: #f5f5f5;
        }

        /* Bottone allineato alla grafica originale */
        .form-container button {
            width: 100%;
            padding: 12px;
            background-color: black;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }

        .buttons_select {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        /* Nasconde i radio button */
        input[type="radio"] {
            display: none;
        }

        /* Evidenziazione immagine selezionata */
        input[type="radio"]:checked + label .img_sceltacapelli {
            border: 3px solid black;
            transform: scale(1.1);
        }

        /* Stili per il contenitore delle immagini cliccabili */
        .img_label {
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
        }

        .form-container input,
        .form-container select  {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            background-color: #f5f5f5;
        }
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

            // === Vincoli checkbox servizi ===
            const mandatoryRelations = {
                2: [1],
                3: [1],
                4: [1],
                5: [1],
                7: [1],
                8: [1],
                9: [1],
            };

            const incompatibleRelations = {
                3: [7],
                4: [7, 8],
                5: [6, 7],
                6: [5, 8],
                7: [3, 4, 5, 6],
                8: [3, 4, 6],
            };

            function updateCheckboxStates(checkbox) {
                const val = parseInt($(checkbox).val());

                if ($(checkbox).is(':checked')) {
                    if (mandatoryRelations[val]) {
                        mandatoryRelations[val].forEach(function(req){
                            const el = $("input[name='checkboxes[]'][value='" + req + "']");
                            if (!el.is(':checked')) el.prop('checked', true);
                        });
                    }
                    $("input[name='checkboxes[]']").each(function(){
                        const cval = parseInt($(this).val());
                        if (incompatibleRelations[val] && incompatibleRelations[val].includes(cval)) {
                            $(this).prop('checked', false);
                            $(this).prop('disabled', true);
                        }
                    });
                } else {
                    $("input[name='checkboxes[]']").prop('disabled', false);
                }
            }

            $("input[name='checkboxes[]']").change(function(){
                updateCheckboxStates(this);
            });

        });
    </script>
</head>
<body>
<div class="container">
    <div class="flex-container">
        <!-- Colonna Sinistra: Ricerca/Registrazione Utente -->
        <div class="col" id="col-user">
            <h2>Ricerca Utente</h2>
            <form method="post" action="">
                <input type="text" name="ricerca" placeholder="Inserisci nome, cognome o email" value="<?php echo isset($_POST['ricerca']) ? htmlspecialchars($_POST['ricerca']) : ''; ?>">
                <button type="submit" name="ricercaUtente">Cerca Utente</button>
            </form>

            <?php if (!empty($messaggioUtente)) : ?>
                <p><?php echo htmlspecialchars($messaggioUtente); ?></p>
            <?php endif; ?>

            <?php if (!empty($utentiTrovati)) : ?>
                <h3>Risultati della ricerca:</h3>
                <ul>
                <?php foreach ($utentiTrovati as $utente) : ?>
                    <li>
                        <?php echo htmlspecialchars($utente['fName'] . ' ' . $utente['lName'] . ' - ' . $utente['email']); ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="selezionaUtente" value="<?php echo $utente['customer_id']; ?>">
                            <button type="submit">Seleziona</button>
                        </form>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php elseif ($utenteSelezionato): ?>
                <p>
                    Utente selezionato: <?php echo htmlspecialchars($utenteSelezionato['fName'] . " " . $utenteSelezionato['lName']); ?><br>
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
                        <div class="hair-option">
                            <input type="radio" id="lunghi" name="lunghezzaCapelli" value="lunghi" required>
                            <label for="lunghi" class="img_label">
                                <img src=".././style/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Capelli Lunghi">
                            </label>
                        </div>
                        <div class="hair-option">
                            <input type="radio" id="corti" name="lunghezzaCapelli" value="corti" required>
                            <label for="corti" class="img_label">
                                <img src=".././style/rullino/CapelliCorti.png" class="img_sceltacapelli" alt="Capelli Corti">
                            </label>
                        </div>
                    </div>
                    <br>
                    <label>Genere:</label>
                    <select id="gender" name="gender" required>
                        <option value="maschio">Uomo</option>
                        <option value="femmina">Donna</option>
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

        <!-- Colonna Destra: Prenotazione Appuntamento (verticale) -->
        <div class="col" id="col-appointment">
            <h2>Prenota Appuntamento</h2>
            <?php if (!empty($messaggioPrenotazione)) : ?>
                <p><?php echo $messaggioPrenotazione; ?></p>
            <?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="cliente_id" value="<?php echo $utenteSelezionato ? $utenteSelezionato['customer_id'] : ''; ?>">

                <fieldset>
                    <legend>Seleziona i servizi</legend>
                    <div class="services-container" style="display:flex; flex-direction: column; gap: 8px;">
                        <label><input type="checkbox" name="checkboxes[]" value="1"> Piega</label>
                        <label><input type="checkbox" name="checkboxes[]" value="2"> Taglio</label>
                        <label><input type="checkbox" name="checkboxes[]" value="3"> Colore</label>
                        <label><input type="checkbox" name="checkboxes[]" value="4"> Mèche/Schiariture</label>
                        <label><input type="checkbox" name="checkboxes[]" value="5"> Permanente</label>
                        <label><input type="checkbox" name="checkboxes[]" value="6"> Stiratura</label>
                        <label><input type="checkbox" name="checkboxes[]" value="7"> Keratina</label>
                        <label><input type="checkbox" name="checkboxes[]" value="8"> Colori - Mèche</label>
                        <label><input type="checkbox" name="checkboxes[]" value="9"> Ricostruzione</label>
                        <label><input type="checkbox" name="checkboxes[]" value="10"> Trattamento</label>
                    </div>
                </fieldset>

                <fieldset>
                    <legend>Data Appuntamento</legend>
                    <input type="text" id="appointment_date" name="appointment_date" autocomplete="off" required>
                </fieldset>

                <fieldset>
                    <legend>Orario Appuntamento</legend>
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

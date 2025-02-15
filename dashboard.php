<?php
// Blocco di controllo accessi
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Recupera il ruolo dell'utente dal database
$sql = "SELECT user_tipe FROM customer WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

// Controllo: solo amministratore e operatrice possono accedere
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}

// Selezione preferenza (modifica)
if (isset($_POST['update_preference'])) {
    $appointmentId = $_POST['appointment_id'];
    $newPreference = $_POST['preference'];

    // Ottieni il customer_id per l'appuntamento
    $customerSql = "SELECT customer_id FROM appointment WHERE appointment_id = ?";
    $customerStmt = $conn->prepare($customerSql);
    $customerStmt->bind_param("i", $appointmentId);
    $customerStmt->execute();
    $customerStmt->bind_result($customerId);
    $customerStmt->fetch();
    $customerStmt->close();

    // Aggiorna la preferenza nel database per il cliente
    $updateSql = "UPDATE customer SET preference = ? WHERE customer_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newPreference, $customerId);
    $updateStmt->execute();
    $updateStmt->close();

    echo "<p>Preferenza aggiornata con successo!</p>";
}

// Recupero degli appuntamenti
$selectedDate = isset($_POST['search_date']) ? $_POST['search_date'] : null;

// Se non viene fornita una data specifica, mostriamo tutti gli appuntamenti
if ($selectedDate) {
    $sql = "
        SELECT 
            a.appointment_id, 
            a.dateTime AS appointment_date, 
            CONCAT(c.fName, ' ', c.lName) AS cliente,
            c.preference,
            s.nameS, 
            s.engageTime 
        FROM appointment a
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN mergeAS m ON a.appointment_id = m.appointment_id
        LEFT JOIN serviceCC s ON m.service_id = s.service_id
        WHERE DATE(a.dateTime) = ?
        ORDER BY a.dateTime ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedDate);
} else {
    $sql = "
        SELECT 
            a.appointment_id, 
            a.dateTime AS appointment_date, 
            CONCAT(c.fName, ' ', c.lName) AS cliente,
            c.preference,
            s.nameS, 
            s.engageTime 
        FROM appointment a
        JOIN customer c ON a.customer_id = c.customer_id
        LEFT JOIN mergeAS m ON a.appointment_id = m.appointment_id
        LEFT JOIN serviceCC s ON m.service_id = s.service_id
        ORDER BY a.dateTime ASC
    ";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

// Organizza gli appuntamenti per preferenza
$appointmentsByPreference = [
    'Barbara' => [],
    'Giulia' => [],
    'Casuale' => []
];

// Raggruppa gli appuntamenti in base alla preferenza e orario
while ($row = $result->fetch_assoc()) {
    // Assicurati che la preferenza sia disponibile, altrimenti usiamo 'Casuale'
    $preference = isset($row['preference']) && $row['preference'] ? $row['preference'] : 'Casuale';
    $appointmentKey = $row['appointment_date']; // Raggruppiamo per data e ora

    // Aggiungi il servizio per l'appuntamento
    $appointmentsByPreference[$preference][$appointmentKey][] = [
        'appointment_id' => $row['appointment_id'],
        'cliente' => $row['cliente'],
        'nameS' => $row['nameS'],
        'engageTime' => $row['engageTime']
    ];
}

$conn->close();
?>

<!DOCTYPE html> 
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Stile per le colonne */
        .column {
            width: 30%;
            float: left;
            margin: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            height: 600px;
            overflow-y: auto;
        }

        .appointment-box {
            margin-bottom: 10px;
            background-color: #f0f0f0;
            padding: 5px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .appointment-box strong {
            font-size: 14px;
        }

        .clearfix {
            clear: both;
        }

        .button-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Pulsante per il Logout -->
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>

    <!-- Barra di ricerca per la data -->
    <form action="" method="POST">
        <input type="date" name="search_date" value="<?php echo $selectedDate ? $selectedDate : ''; ?>" placeholder="Seleziona una data">
        <button type="submit">Cerca Appuntamenti</button>
    </form>
    <form action="" method="POST">
        <button type="submit" name="search_date" value="">Mostra tutti gli appuntamenti</button>
    </form>

    <h2>Appuntamenti per il giorno: <?php echo $selectedDate ? date("d-m-Y", strtotime($selectedDate)) : "Tutti i Giorni"; ?></h2>

    <!-- Colonne per le preferenze -->
    <div class="column" style="background-color: #f8d7da;">
        <h3>Preferenza: Barbara</h3>
        <?php
        if (!empty($appointmentsByPreference['Barbara'])) {
            foreach ($appointmentsByPreference['Barbara'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="column" style="background-color: #d1ecf1;">
        <h3>Preferenza: Giulia</h3>
        <?php
        if (!empty($appointmentsByPreference['Giulia'])) {
            foreach ($appointmentsByPreference['Giulia'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="column" style="background-color: #d4edda;">
        <h3>Preferenza: Casuale</h3>
        <?php
        if (!empty($appointmentsByPreference['Casuale'])) {
            foreach ($appointmentsByPreference['Casuale'] as $appointmentKey => $appointments) {
                echo "<div class='appointment-box'>";
                // Stampa il nome cliente solo una volta per ogni appuntamento
                echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                foreach ($appointments as $appt) {
                    echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>"; // Stampa solo il servizio
                }
                // Aggiungi il form di modifica della preferenza una sola volta
                echo "<form action='' method='POST'>
                        <input type='hidden' name='appointment_id' value='" . $appointments[0]['appointment_id'] . "'>
                        <select name='preference'>
                            <option value='Barbara' " . ($preference == 'Barbara' ? 'selected' : '') . ">Barbara</option>
                            <option value='Giulia' " . ($preference == 'Giulia' ? 'selected' : '') . ">Giulia</option>
                            <option value='Casuale' " . ($preference == 'Casuale' ? 'selected' : '') . ">Casuale</option>
                        </select>
                        <button type='submit' name='update_preference'>Modifica Preferenza</button>
                      </form>";
                echo "</div>";
            }
        } else {
            echo "<p>Nessun appuntamento.</p>";
        }
        ?>
    </div>

    <div class="clearfix"></div>

    <!-- Sezione pulsanti -->
    <div class="button-container" style="display: flex; gap: 10px;">
        <form action="prenota.php" method="post">
            <button type="submit">Aggiungi Appuntamento</button>
        </form>
        <form action="calendario.php" method="get">
            <button type="submit">Calendario</button>
        </form>
        <form action="lista_clienti.php" method="get">
            <button type="submit">Gestione Clienti</button>
        </form>
        <form action="aggiungi_utente.php" method="get">
            <button type="submit">Aggiungi Cliente</button>
        </form>
        <form action="cerca_appuntamento.php" method="get">
            <button type="submit">Modifica Appuntamento</button>
        </form>
    </div>

</body>
</html>

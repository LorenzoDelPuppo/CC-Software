<?php
// Blocco di controllo accessi
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . './../add-edit/cancella_appuntamenti.php'; 

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit;
}

$email = $_SESSION['email'];

// Recupera il ruolo dell'utente dal database
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
    $updateSql = "UPDATE Customer SET preference = ? WHERE customer_id = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("si", $newPreference, $customerId);
    $updateStmt->execute();
    $updateStmt->close();

    echo "<p>Preferenza aggiornata con successo!</p>";
}

// Gestisci la selezione della data
$selectedDate = isset($_POST['search_date']) ? $_POST['search_date'] : null;

// Se non viene fornita una data specifica (tutti gli appuntamenti), seleziona la data corrente
if (!$selectedDate && !isset($_POST['show_all'])) {
    $selectedDate = date('Y-m-d');  // Imposta la data corrente come predefinita
}

// Recupero degli appuntamenti
if ($selectedDate) {
    // Se la data è selezionata, mostra gli appuntamenti per quel giorno
    $sql = "
        SELECT 
            a.appointment_id, 
            a.dateTime AS appointment_date, 
            CONCAT(c.fName, ' ', c.lName) AS cliente,
            c.preference,
            s.nameS, 
            s.engageTime 
        FROM appointment a
        JOIN Customer c ON a.customer_id = c.customer_id
        LEFT JOIN mergeAS m ON a.appointment_id = m.appointment_id
        LEFT JOIN serviceCC s ON m.service_id = s.service_id
        WHERE DATE(a.dateTime) = ?
        ORDER BY a.dateTime ASC
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $selectedDate);
} else {
    // Se non è stata selezionata una data, mostra tutti gli appuntamenti
    $sql = "
        SELECT 
            a.appointment_id, 
            a.dateTime AS appointment_date, 
            CONCAT(c.fName, ' ', c.lName) AS cliente,
            c.preference,
            s.nameS, 
            s.engageTime 
        FROM appointment a
        JOIN Customer c ON a.customer_id = c.customer_id
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
    <link rel="stylesheet" href="../CC-Software/style/style_dash.css">
    <style>
        /* Impostazioni per il contenitore delle colonne */
        .column-container {
            display: flex;
            gap: 20px; /* Spaziatura tra le colonne */
            justify-content: space-between;
            flex-wrap: wrap; /* Permette di andare a capo se lo schermo è troppo piccolo */
        }

        /* Stile per le singole colonne */
        .column {
            flex: 1 1 30%; /* 1 1 30% significa che la colonna può occupare fino al 30% della larghezza, ma può adattarsi */
            padding: 10px;
            border: 1px solid #ccc;
            height: 600px;
            overflow-y: auto;
        }

        /* Aggiungi uno stile per gli sfondi */
        .column:nth-child(1) {
            background-color: #D3D3D3;
        }

        .column:nth-child(2) {
            background-color: #A9A9A9;
        }

        .column:nth-child(3) {
            background-color: #808080;
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
    </style>
</head>
<?php include '.././view-get/barra.php'; ?>
<body>

    <!-- Barra di ricerca per la data -->
    <form action="" method="POST">
        <input type="date" name="search_date" value="<?php echo $selectedDate ? $selectedDate : ''; ?>" placeholder="Seleziona una data">
        <button type="submit">Cerca Appuntamenti</button>
    </form>
    <!-- Bottone per mostrare tutti gli appuntamenti -->
    <form action="" method="POST">
        <button type="submit" name="show_all" value="1">Mostra tutti gli appuntamenti</button>
    </form>

    <h2>Appuntamenti per il giorno: <?php echo $selectedDate ? date("d-m-Y", strtotime($selectedDate)) : "Tutti i Giorni"; ?></h2>

    <!-- Contenitore per le colonne -->
    <div class="column-container">
        <!-- Colonna Barbara -->
        <div class="column">
            <h3>Preferenza: Barbara</h3>
            <?php
            if (!empty($appointmentsByPreference['Barbara'])) {
                foreach ($appointmentsByPreference['Barbara'] as $appointmentKey => $appointments) {
                    echo "<div class='appointment-box'>";
                    echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                    echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                    foreach ($appointments as $appt) {
                        echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>";
                    }
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

        <!-- Colonna Giulia -->
        <div class="column">
            <h3>Preferenza: Giulia</h3>
            <?php
            if (!empty($appointmentsByPreference['Giulia'])) {
                foreach ($appointmentsByPreference['Giulia'] as $appointmentKey => $appointments) {
                    echo "<div class='appointment-box'>";
                    echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                    echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                    foreach ($appointments as $appt) {
                        echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>";
                    }
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

        <!-- Colonna Casuale -->
        <div class="column">
            <h3>Preferenza: Casuale</h3>
            <?php
            if (!empty($appointmentsByPreference['Casuale'])) {
                foreach ($appointmentsByPreference['Casuale'] as $appointmentKey => $appointments) {
                    echo "<div class='appointment-box'>";
                    echo "<strong>Cliente: " . $appointments[0]['cliente'] . "</strong><br>";
                    echo "<strong>Data: " . date("d-m-Y H:i", strtotime($appointmentKey)) . "</strong><br>";
                    foreach ($appointments as $appt) {
                        echo $appt['nameS'] . " (" . $appt['engageTime'] . " min)<br>";
                    }
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
    </div>

</body>
</html>

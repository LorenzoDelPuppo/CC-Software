<?php
// Blocco di controllo accessi
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/../../vendor/autoload.php';
 // Carica PHPMailer

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
            c.email,
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
            c.email,
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
        'engageTime' => $row['engageTime'],
        'email' => $row['email']
    ];

    // Invio email di promemoria
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'noreplychecapelli@gmail.com';
        $mail->Password = 'jiyk rpbo uzcg cdee';  // Usa app password per maggiore sicurezza
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Impostazioni email
        $mail->setFrom('noreplychecapelli@gmail.com', 'Che Capelli');
        $mail->addAddress($row['email'], $row['cliente']);
        $mail->addReplyTo('noreplychecapelli@gmail.com', 'Che Capelli');
        $mail->isHTML(true);
        $mail->Subject = 'Promemoria Appuntamento - Che Capelli';

        // Formattazione data e ora
        $date = date('d/m/Y', strtotime($row['appointment_date']));
        $time = date('H:i', strtotime($row['appointment_date']));

        $mail->Body = "
            <p>Ciao <b>{$row['cliente']}</b>,</p>
            <p>Ti ricordiamo che hai un appuntamento presso <b>Che Capelli</b> il giorno <b>{$date}</b> alle <b>{$time}</b>.</p>
            <p>Ti aspettiamo!</p>
            <p><i>Questa è un'email automatica, non rispondere a questo messaggio.</i></p>
        ";

        $mail->send();
        echo "Promemoria inviato a {$row['email']}<br>";

    } catch (Exception $e) {
        echo "Errore nell'invio dell'email: {$mail->ErrorInfo}";
    }
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
        /* Aggiungi qui il tuo CSS personalizzato */
    </style>
</head>
<body>
    <!-- Aggiungi il contenuto della tua dashboard -->
</body>
</html>

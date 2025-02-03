<?php
session_start();
include 'config.php'; // Connessione al database

// Controlla se il cliente è loggato
if (!isset($_SESSION['customer_id'])) {
    die("Errore: Devi effettuare il login per prenotare un appuntamento.");
}

$customer_id = $_SESSION['customer_id']; // Recupera ID cliente dalla sessione

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['checkboxes']) && is_array($_POST['checkboxes']) && isset($_POST['appointment_date'])) {
        $appointment_date = $_POST['appointment_date'];

        // Inserisce un nuovo appuntamento
        $sql = "INSERT INTO appointment (customer_id, dateTime) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $customer_id, $appointment_date);

        if ($stmt->execute()) {
            $appointment_id = $stmt->insert_id; // Ottiene l'ID dell'appuntamento
            echo "Appuntamento prenotato con successo!<br>";

            // Inserisce i servizi associati
            $sql = "INSERT INTO servicesOfAppointment (appointment_id, service_id, sPera) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            foreach ($_POST['checkboxes'] as $service_id) {
                $service_id = intval($service_id);
                $sPera = "default_value"; // Valore predefinito
                $stmt->bind_param("iis", $appointment_id, $service_id, $sPera);
                $stmt->execute();
            }

            echo "Servizi registrati con successo!";
        } else {
            echo "Errore nella prenotazione: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Errore: seleziona almeno un servizio e una data.";
    }
}

$conn->close();
?>

<!-- Form di prenotazione -->
<form method="post">
    <label for="appointment_date">Seleziona data:</label>
    <input type="datetime-local" name="appointment_date" required><br><br>

    <label>Seleziona i servizi:</label><br>
    <input type="checkbox" name="checkboxes[]" value="1"> Servizio 1<br>
    <input type="checkbox" name="checkboxes[]" value="2"> Servizio 2<br>
    <input type="checkbox" name="checkboxes[]" value="3"> Servizio 3<br>
    <input type="checkbox" name="checkboxes[]" value="4"> Servizio 4<br>

    <button type="submit">Prenota</button>
</form>

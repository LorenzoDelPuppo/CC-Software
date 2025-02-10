<?php
session_start(); // Avvia la sessione

// Se l'utente è già loggato, reindirizza alla pagina prenotatest.php
if (isset($_SESSION['email'])) {
    header("Location: prenotatest.php");
    exit();
}

require_once "connect.php"; // Include il file di connessione al database

// Gestione dell'invio del form
if (isset($_POST['submit'])) {
    // Recupera le checkbox selezionate e la data
    $selectedCheckboxes = isset($_POST['checkboxes']) ? $_POST['checkboxes'] : array();
    $selectedDate = trim($_POST['data_prenotazione']);

    // Salva i dati nella sessione (opzionale, per utilizzi futuri)
    $_SESSION['checkboxes'] = $selectedCheckboxes;
    $_SESSION['data_prenotazione'] = $selectedDate;

    // Converte l'array delle checkbox in una stringa (ad es. CSV) per salvarlo nel database
    $checkboxesSerialized = implode(',', $selectedCheckboxes);

    // Query parametrizzata per inserire i dati nella tabella "prenotazioni"
    $query = "INSERT INTO prenotazioni (checkboxes, data_prenotazione) VALUES (?, ?)";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ss", $checkboxesSerialized, $selectedDate);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "Prenotazione inserita con successo!";
        } else {
            $message = "Errore durante l'inserimento.";
        }
        $stmt->close();
    } else {
        $message = "Errore nella preparazione della query.";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Prenotazione</title>
    <!-- Includi i CSS e JS necessari per il calendario (ad es. jQuery UI) -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script>
        $(document).ready(function(){
            // Inizializza il datepicker sul campo della data
            $("#data_prenotazione").datepicker({
                dateFormat: 'yy-mm-dd'
            });
        });
    </script>
</head>
<body>
    <?php
    // Visualizza eventuali messaggi di conferma o errore
    if (isset($message)) {
        echo "<p>$message</p>";
    }
    ?>
    <!-- Form per la prenotazione -->
    <form method="POST" action="">
        <fieldset>
            <legend>Seleziona le opzioni</legend>
            <?php
            // Recupera le opzioni dal database per generare le checkbox (funzionalità di test.php)
            $queryOptions = "SELECT id, nome FROM opzioni";
            if ($result = $conn->query($queryOptions)) {
                while ($row = $result->fetch_assoc()) {
                    echo '<label>';
                    echo '<input type="checkbox" name="checkboxes[]" value="' . $row['id'] . '"> ' . htmlspecialchars($row['nome']);
                    echo '</label><br>';
                }
                $result->free();
            } else {
                echo "Errore nel recupero delle opzioni.";
            }
            ?>
        </fieldset>
        <fieldset>
            <legend>Seleziona la data</legend>
            <label for="data_prenotazione">Data Prenotazione:</label>
            <input type="text" id="data_prenotazione" name="data_prenotazione" autocomplete="off">
        </fieldset>
        <button type="submit" name="submit">Prenota</button>
    </form>
</body>
</html>

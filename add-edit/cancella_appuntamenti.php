<?php
// Connessione al database
require_once __DIR__ . '/../connect.php';

// Ottieni la data e ora attuale
$currentDateTime = date("Y-m-d H:i:s");
//echo "Data e ora corrente: " . $currentDateTime . "<br>"; // Debug per la data corrente

// Query per selezionare gli appuntamenti passati
$sql = "DELETE FROM appointment WHERE dateTime < ?";
//echo "Query: " . $sql . " | DateTime: " . $currentDateTime . "<br>"; // Debug della query

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $currentDateTime);
    if ($stmt->execute()) {
        //echo "Gli appuntamenti passati sono stati cancellati con successo.";
    } else {
        $stmt->error;
    }
    $stmt->close();
} else {
    echo "Errore nella preparazione della query.";
}

?>

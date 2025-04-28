<?php
// Connessione al database, collegandosi al file 
require_once __DIR__ . '/../connect.php';

// Ottieni la data e ora attuale
$currentDateTime = date("Y-m-d H:i:s");

// Query per selezionare gli appuntamenti passati
$sql = "DELETE FROM appointment WHERE dateTime < ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $currentDateTime);
    if ($stmt->execute()) {
        echo "Gli appuntamenti passati sono stati cancellati con successo.";
    } else {
        echo "Errore durante la cancellazione degli appuntamenti: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "Errore nella preparazione della query.";
}

$conn->close();
?>
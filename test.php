<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recupera il giorno e l'orario inviati dal form
    $giorno = $_POST['giorno'];
    $orario = $_POST['orario'];

    // Mappa i giorni della settimana, senza lunedì
    $giorni_settimana = [
        "martedì" => 1,
        "mercoledì" => 2,
        "giovedì" => 3,
        "venerdì" => 4,
        "sabato" => 5
    ];

    // Crea la data e ora
    $data = new DateTime();

    // Calcola il giorno della settimana in base al giorno selezionato
    $giorno_della_settimana = $giorni_settimana[$giorno];
    $giorno_attuale = $data->format('w'); // Ottieni il giorno attuale (0 = domenica, 6 = sabato)

    // Se il giorno selezionato è uguale o successivo all'attuale, usa il giorno selezionato
    if ($giorno_della_settimana >= $giorno_attuale) {
        $data->modify('this ' . $giorno); // Usa il giorno selezionato
    } else {
        // Altrimenti, usa il prossimo giorno disponibile
        $data->modify('next ' . $giorno);
    }

    // Aggiungi l'orario selezionato
    $data->setTime(
        (int)substr($orario, 0, 2),    // Ore
        (int)substr($orario, 3, 2)     // Minuti
    );

    // Stampa la data finale senza i secondi
    echo "Data e ora selezionata: " . $data->format('Y-m-d H:i'); // Senza i secondi
} else {
    // Mostra il modulo se non è stato inviato
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleziona Giorno e Orario</title>
    <script>
        // Funzione per aggiornare gli orari disponibili in base al giorno selezionato
        function aggiornaOrari() {
            var giorno = document.getElementById("giorno").value;
            var orariSelect = document.getElementById("orario");
            var orariMartediVenerdi = [
                "08:30", "08:45", "09:00", "09:15", "09:30", "09:45", "10:00", "10:15", "10:30", "10:45", "11:00", "11:15", "11:30", "11:45", "12:00", "12:15", "12:30",
                "15:00", "15:15", "15:30", "15:45", "16:00", "16:15", "16:30", "16:45", "17:00", "17:15", "17:30", "17:45", "18:00", "18:15", "18:30", "18:45", "19:00"
            ];
            var orariSabato = [
                "08:00", "08:15", "08:30", "08:45", "09:00", "09:15", "09:30", "09:45", "10:00", "10:15", "10:30", "10:45", "11:00", "11:15", "11:30", "11:45", "12:00", "12:15", "12:30",
                "13:00", "13:15", "13:30", "13:45", "14:00", "14:15", "14:30", "14:45", "15:00", "15:15", "15:30", "15:45", "16:00", "16:15", "16:30", "16:45", "17:00"
            ];
            // Reset orari
            orariSelect.innerHTML = "";
            
            if (giorno === "sabato") {
                // Aggiungi orari per sabato
                orariSabato.forEach(function(orario) {
                    var option = document.createElement("option");
                    option.value = orario;
                    option.textContent = orario;
                    orariSelect.appendChild(option);
                });
            } else {
                // Aggiungi orari per martedì - venerdì
                orariMartediVenerdi.forEach(function(orario) {
                    var option = document.createElement("option");
                    option.value = orario;
                    option.textContent = orario;
                    orariSelect.appendChild(option);
                });
            }
        }

        // Esegui l'aggiornamento degli orari quando la pagina è pronta
        window.onload = aggiornaOrari;
    </script>
</head>
<body>
    <h2>Seleziona Giorno e Orario</h2>
    <form action="" method="POST">
        <!-- Giorno della settimana (Martedì - Sabato) -->
        <label for="giorno">Seleziona Giorno:</label>
        <select name="giorno" id="giorno" required onchange="aggiornaOrari()">
            <option value="martedì">Martedì</option>
            <option value="mercoledì">Mercoledì</option>
            <option value="giovedì">Giovedì</option>
            <option value="venerdì">Venerdì</option>
            <option value="sabato">Sabato</option>
        </select>
        <br><br>

        <!-- Selezione dell'orario -->
        <label for="orario">Seleziona Orario:</label>
        <select name="orario" id="orario" required>
            <!-- Gli orari verranno popolati dinamicamente tramite JavaScript -->
        </select>
        <br><br>

        <input type="submit" value="Invia">
    </form>
</body>
</html>
<?php
}
?>

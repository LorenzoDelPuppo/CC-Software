<?php
if (!isset($_GET['date'])) {
    die("Data non fornita.");
}

$date = $_GET['date']; // Data selezionata nel formato YYYY-MM-DD
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fascia Oraria Appuntamenti</title>
    <script src="../js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href="../style/barra_alta.css">

    <style>
    #calendar-container {
        width: 100%;
        max-width: 1200px;
        margin: auto;
        padding: 20px;
    }

    #calendar-grid {
        position: relative;
        width: 100%;
        min-height: 2400px; /* Estensione verticale per coprire tutta la giornata */
        border-left: 2px solid #ccc;
        padding-left: 60px;
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(5px);
        overflow-y: visible;
    }

    .time-slot {
        height: 31px !important; /* 15 minuti = 31px */
        border-bottom: 1px solid #ddd;
        font-size: 14px;
        position: relative;
    }

    .appointment {
        position: absolute;
        left: 120px; /* In futuro può cambiare per multi-colonna */
        width: 250px;
        color: white;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
        background-color: rgba(50, 205, 50, 0.9);
        overflow: hidden;
        z-index: 10;
    }
</style>

</head>
<?php include '../view-get/barra.php'; ?>
<body>

<div id="calendar-container">
    <h2 style="text-align: center;">Appuntamenti per il giorno <?php echo date("d-m-Y", strtotime($date)); ?></h2>

    <div id="calendar-grid">
        <?php
        for ($hour = 8; $hour <= 19; $hour++) {
            for ($min = 0; $min < 60; $min += 15) {
                $time = sprintf("%02d:%02d", $hour, $min);
                echo "<div class='time-slot'><strong>$time</strong></div>";
            }
        }
        ?>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let selectedDate = "<?php echo $date; ?>"; 

        fetch(`../view-get/getAppointments.php?date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                console.log("📦 Appuntamenti ricevuti:", data); // LOG DEBUG

                if (!data.error) {
                    renderAppointments(data);
                } else {
                    alert("Errore nel caricamento degli appuntamenti: " + data.error);
                }
            })
            .catch(error => console.error("❌ Errore nel caricamento degli appuntamenti:", error));
    });

    // Calcola la posizione verticale nella griglia (che parte da 08:00)
    function getTimePosition(time) {
    // Preleva solo ore e minuti
    let [hours, minutes] = time.substring(0, 5).split(":").map(Number);
    let totalMinutes = (hours * 60) + minutes;

    const gridStartMinutes = 480; // 08:00
    let diffMinutes = totalMinutes - gridStartMinutes;

    return (diffMinutes / 15) * 31 ; // ✅ Offset corretto: 1 blocco in basso
}



    function renderAppointments(appointments) {
        let container = document.getElementById("calendar-grid");

        if (!Array.isArray(appointments) || appointments.length === 0) {
            console.warn("⚠️ Nessun appuntamento disponibile o dati malformati");
            return;
        }

        appointments.forEach(appt => {
            console.log("▶️ Disegno:", appt); // LOG DEBUG

            let position = getTimePosition(appt.startTime);
            let duration = appt.totalDuration;

            let appointmentDiv = document.createElement("div");
            appointmentDiv.classList.add("appointment");
            appointmentDiv.style.top = `${position}px`;
            appointmentDiv.style.height = `${(duration / 15) * 31}px`;

            let services = Array.isArray(appt.services)
                ? appt.services.map(s => s.name).join(", ")
                : "Servizi non disponibili";

            appointmentDiv.innerHTML = `<strong>${appt.customer}</strong><br>${services}`;

            container.appendChild(appointmentDiv);
        });
    }
</script>

</body>
</html>

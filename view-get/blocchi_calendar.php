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
            min-height: 2400px;
            border-left: 2px solid #ccc;
            padding-left: 60px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
            overflow-y: visible;
            
        }

        .time-slot {
            height: 31px !important;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            position: relative;
        }

        .appointment {
    position: absolute;
    box-sizing: border-box;
    padding: 5px;
    margin: 0;
    font-size: 12px;
    color: white;
    border-radius: 5px;
    background-color: rgba(50, 205, 50, 0.9);
    box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
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
                console.log("📦 Appuntamenti ricevuti:", data);
                if (!data.error) {
                    renderAppointments(data);
                } else {
                    alert("Errore nel caricamento degli appuntamenti: " + data.error);
                }
            })
            .catch(error => console.error("❌ Errore nel caricamento degli appuntamenti:", error));
    });

    function getTimePosition(time) {
    let [hours, minutes] = time.split(":").map(Number);
    let totalMinutes = (hours * 60) + minutes;
    const gridStartMinutes = 480; // 08:00
    let diffMinutes = totalMinutes - gridStartMinutes;
    return (diffMinutes / 15) * 31;
}



    function renderAppointments(appointments) {
        const container = document.getElementById("calendar-grid");

        if (!Array.isArray(appointments) || appointments.length === 0) {
            console.warn("⚠️ Nessun appuntamento disponibile o dati malformati");
            return;
        }

        // Raggruppa per orario normalizzato HH:MM
        const groupedByTime = {};
        appointments.forEach(appt => {
            const time = appt.startTime.substring(0, 5);
            if (!groupedByTime[time]) groupedByTime[time] = [];
            groupedByTime[time].push(appt);
        });

        // Ciclo per ogni gruppo orario
        Object.entries(groupedByTime).forEach(([time, group]) => {
            const count = group.length;
            const slotWidth = 250; // larghezza totale disponibile per gli appuntamenti affiancati
            const appointmentWidth = slotWidth / count;

            group.forEach((appt, indexInGroup) => {
                const start = appt.startTime.substring(0, 5);
                const position = getTimePosition(start);
                const duration = appt.totalDuration;

                const appointmentDiv = document.createElement("div");
                appointmentDiv.classList.add("appointment");

                appointmentDiv.style.top = `${position}px`;
                appointmentDiv.style.left = `${60 + indexInGroup * appointmentWidth}px`;
                appointmentDiv.style.width = `${appointmentWidth - 5}px`;
                appointmentDiv.style.height = `${(duration / 15) * 31}px`;

                const services = Array.isArray(appt.services)
                    ? appt.services.map(s => s.name).join(", ")
                    : "Servizi non disponibili";

                appointmentDiv.innerHTML = `<strong>${appt.customer}</strong><br>${services}`;
                container.appendChild(appointmentDiv);
            });
        });
    }

</script>

</body>
</html>

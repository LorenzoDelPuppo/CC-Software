<?php
if (!isset($_GET['date'])) {
    die("Data non fornita.");
}

$date = $_GET['date']; // Data selezionata nel formato YYYY-MM-DD

// Colori definiti per ogni servizio
$serviceColors = [
    'Piega'               => '#e74c3c', // rosso
    'Taglio'              => '#3498db', // blu
    'Colore'              => '#2ecc71', // verde
    'Mèche - Schiariture' => '#f1c40f', // giallo
    'Permanente'          => '#9b59b6', // viola
    'Stiratura'           => '#1abc9c', // turchese
    'Keratina'            => '#e84393', // rosa acceso
    'Colori - Mèche'      => '#34495e', // blu scuro/grigio
    'Ricostruzione'       => '#e67e22', // arancione
    'Trattamento'         => '#7f8c8d'  // grigio
];
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Fascia Oraria Appuntamenti</title>
    <script src="../js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href="../style/barra_alta.css" />

    <style>
        body {
            margin: 0;
            padding-right: 180px; /* spazio per la legenda */
            font-family: Arial, sans-serif;
        }

        #calendar-container {
            width: 100%;
            max-width: 1200px;
            margin: auto;
            padding: 20px;
        }

        #calendar-wrapper {
            display: flex;
        }

        /* Colonna orari */
        #time-column {
            width: 60px;
            border-right: 2px solid #ccc;
        }

        /* Righe orari */
        .time-slot {
            height: 31px;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            box-sizing: border-box;
        }

        #time-column .time-slot {
            text-align: right;
            padding-right: 5px;
            background: #fff;
        }

        /* Griglia appuntamenti */
        #calendar-grid {
            position: relative;
            flex-grow: 1;
            background-color: #fdfdfd;
            min-height: calc(12 * 4 * 31px); /* 12 ore * 4 slot per ora * 31px altezza */
        }

        #calendar-grid .time-slot {
            position: relative;
        }

        /* Blocchi appuntamento */
        .appointment {
            position: absolute;
            box-sizing: border-box;
            padding: 5px 5px 5px 8px;
            margin: 0;
            font-size: 12px;
            color: white;
            border-radius: 5px;
            background-color: #333; /* fallback */
            box-shadow: 2px 2px 5px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            z-index: 10;
            display: flex;
            flex-direction: column;
            gap: 6px;
            font-weight: bold;
        }

        /* Pallini colorati per servizi */
        .service-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            flex-shrink: 0;
            border: 1px solid #fff;
            margin-right: 4px;
        }

        /* Contenitore per pallini colorati */
        .dots-container {
            display: flex;
            flex-wrap: wrap;
            gap: 3px;
        }

        /* Legenda fissa a destra */
        #service-legend {
            position: fixed;
            top: 80px;
            right: 10px;
            width: 160px;
            background-color: #f9f9f9;
            border: 1px solid #ccc;
            border-radius: 8px;
            padding: 10px 15px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-size: 14px;
            z-index: 1000;
        }

        #service-legend h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 16px;
            text-align: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .legend-color {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            margin-right: 8px;
            border: 1px solid #999;
        }
    </style>
</head>

<?php include '../view-get/barra.php'; ?>
<body>

<div id="calendar-container">
    <h2 style="text-align: center;">Appuntamenti per il giorno <?php echo date("d-m-Y", strtotime($date)); ?></h2>

    <div id="calendar-wrapper">
        <!-- Colonna orari -->
        <div id="time-column">
            <?php
            for ($hour = 8; $hour <= 19; $hour++) {
                for ($min = 0; $min < 60; $min += 15) {
                    $time = sprintf("%02d:%02d", $hour, $min);
                    echo "<div class='time-slot'><strong>$time</strong></div>";
                }
            }
            ?>
        </div>

        <!-- Griglia appuntamenti -->
        <div id="calendar-grid">
            <?php
            for ($hour = 8; $hour <= 19; $hour++) {
                for ($min = 0; $min < 60; $min += 15) {
                    echo "<div class='time-slot'></div>";
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Legenda fissa a destra -->
<div id="service-legend">
    <h3>Legenda Servizi</h3>
    <?php foreach ($serviceColors as $serviceName => $color): ?>
        <div class="legend-item">
            <div class="legend-color" style="background-color: <?php echo htmlspecialchars($color); ?>;"></div>
            <div><?php echo htmlspecialchars($serviceName); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        let selectedDate = "<?php echo $date; ?>";

        fetch(`../view-get/getAppointments.php?date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    renderAppointments(data);
                } else {
                    alert("Errore nel caricamento degli appuntamenti: " + data.error);
                }
            })
            .catch(error => console.error("Errore nel caricamento degli appuntamenti:", error));
    });

    function getTimePosition(time) {
        let [hours, minutes] = time.split(":").map(Number);
        let totalMinutes = hours * 60 + minutes;
        const gridStartMinutes = 8 * 60; // 08:00
        let diffMinutes = totalMinutes - gridStartMinutes;
        return (diffMinutes / 15) * 31;
    }

    function renderAppointments(appointments) {
        const container = document.getElementById("calendar-grid");
        if (!Array.isArray(appointments) || appointments.length === 0) {
            console.warn("Nessun appuntamento disponibile o dati malformati");
            return;
        }

        const serviceColors = <?php echo json_encode($serviceColors); ?>;

        // Raggruppa appuntamenti per orario
        const groupedByTime = {};
        appointments.forEach(appt => {
            const time = appt.startTime.substring(0, 5);
            if (!groupedByTime[time]) groupedByTime[time] = [];
            groupedByTime[time].push(appt);
        });

        Object.entries(groupedByTime).forEach(([time, group]) => {
            const count = group.length;
            const slotWidth = 250;
            const appointmentWidth = slotWidth / count;

            group.forEach((appt, indexInGroup) => {
                const start = appt.startTime.substring(0, 5);
                const position = getTimePosition(start);
                const duration = appt.totalDuration;

                const appointmentDiv = document.createElement("div");
                appointmentDiv.classList.add("appointment");
                appointmentDiv.style.top = `${position}px`;
                appointmentDiv.style.left = `${indexInGroup * appointmentWidth}px`;
                appointmentDiv.style.width = `${appointmentWidth - 5}px`;
                appointmentDiv.style.height = `${(duration / 15) * 31}px`;

                // Inserisco testo cliente + servizi
                const clientSpan = document.createElement("span");
                clientSpan.textContent = appt.customer;
                clientSpan.style.whiteSpace = "nowrap";
                appointmentDiv.appendChild(clientSpan);

                const servicesSpan = document.createElement("span");
                if (Array.isArray(appt.services) && appt.services.length > 0) {
                    servicesSpan.textContent = appt.services.map(s => s.name).join(", ");
                } else {
                    servicesSpan.textContent = "Servizi non disponibili";
                }
                servicesSpan.style.fontSize = "10px";
                servicesSpan.style.whiteSpace = "normal";
                appointmentDiv.appendChild(servicesSpan);

                // Contenitore pallini
                const dotsContainer = document.createElement("div");
                dotsContainer.classList.add("dots-container");

                if (Array.isArray(appt.services) && appt.services.length > 0) {
                    appt.services.forEach(service => {
                        const dot = document.createElement("span");
                        dot.classList.add("service-dot");
                        dot.style.backgroundColor = serviceColors[service.name] || "#777";
                        dotsContainer.appendChild(dot);
                    });
                } else {
                    const dot = document.createElement("span");
                    dot.classList.add("service-dot");
                    dot.style.backgroundColor = "#777";
                    dotsContainer.appendChild(dot);
                }

                appointmentDiv.appendChild(dotsContainer);

                container.appendChild(appointmentDiv);
            });
        });
    }
</script>

</body>
</html>

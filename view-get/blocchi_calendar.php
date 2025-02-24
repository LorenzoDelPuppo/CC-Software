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
    <script src=".././js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href=".././style/barra_alta.css">

    <style>
        #calendar-container {
            width: 600px;
            margin: auto;
        }

        #calendar-grid {
            position: relative;
            width: 100%;
            height: auto;
            min-height: 1000px;
            border-left: 2px solid #ccc;
            padding-left: 20px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(5px);
        }

        .time-slot {
            height: 31px !important; /* Corretto valore per il posizionamento */
            border-bottom: 1px solid #ddd;
            font-size: 14px;
            position: relative;
        }

        .appointment {
            position: absolute;
            left: 60px;
            width: 250px;
            color: white;
            padding: 5px;
            border-radius: 5px;
            font-size: 12px;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            background-color: rgba(50, 205, 50, 0.9);
            overflow: hidden;
        }
    </style>
</head>
<?php include '.././view-get/barra.php'; ?>
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

        fetch(`.././view-get/getAppointments.php?date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error) {
                    renderAppointments(data);
                }
            })
            .catch(error => console.error("❌ Errore nel caricamento degli appuntamenti:", error));
    });

    function getTimePosition(time) {
        let [hours, minutes] = time.split(":").map(Number);
        let totalMinutes = (hours * 60) + minutes;

        let openingHours = { 2: 510, 3: 510, 4: 510, 5: 510, 6: 480 };
        let selectedDay = new Date("<?php echo $date; ?>").getDay();
        let openingMinutes = openingHours[selectedDay] || 480;

        let diffMinutes = totalMinutes - openingMinutes;
        if (diffMinutes < 0) {
            return 0;
        }

        return (diffMinutes / 15) * 31 + 92; // Aggiunti 30 minuti (62px) per correggere lo shift
    }

    function renderAppointments(appointments) {
        let container = document.getElementById("calendar-grid");

        appointments.forEach(appt => {
            let position = getTimePosition(appt.startTime);
            let duration = appt.totalDuration;

            let appointmentDiv = document.createElement("div");
            appointmentDiv.classList.add("appointment");
            appointmentDiv.style.top = `${position}px`;
            appointmentDiv.style.height = `${Math.max(15, duration * 2)}px`;
            appointmentDiv.innerHTML = `<strong>${appt.customer}</strong><br>${appt.services.map(s => s.name).join(", ")}`;

            container.appendChild(appointmentDiv);
        });
    }
</script>

</body>
</html>

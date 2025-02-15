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
    <script src="menu_profilo.js" defer></script>
    <link rel="stylesheet" href="style/barra_alta.css">

    <style>
        #calendar-container {
            width: 600px;
            margin: auto;
        }

        #calendar-grid {
            position: relative;
            width: 100%;
            height: 1000px;
            border-left: 2px solid #ccc;
            padding-left: 20px;
            background: #f9f9f9;
        }

        .time-slot {
            height: 30px;
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
        }

        .closed-day {
            text-align: center;
            color: red;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href="menu.php">
      <img src="style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href="profilo.php" class="dropdown-item">Profilo</a>
    <a href="impostazioni.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href="logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>

</div>
<body>

<div id="calendar-container">
    <h2 style="text-align: center;">Appuntamenti per il giorno <?php echo date("d-m-Y", strtotime($date)); ?></h2>

    <?php
    // Determinare se il giorno selezionato è un giorno di chiusura
    $dayOfWeek = date('N', strtotime($date)); // 1 = Lunedì, 7 = Domenica

    if ($dayOfWeek == 7 || $dayOfWeek == 1) { // Domenica o Lunedì
        echo "<div class='closed-day'>⚠ Chiuso - Nessun appuntamento disponibile</div>";
    } else {
        echo "<div id='calendar-grid'>";
        $times = [];

        // Definizione degli orari di apertura
        if ($dayOfWeek >= 2 && $dayOfWeek <= 5) { // Martedì - Venerdì
            $times = [
                "08:30", "08:45", "09:00", "09:15", "09:30", "09:45",
                "10:00", "10:15", "10:30", "10:45", "11:00", "11:15", "11:30", "11:45",
                "12:00", "12:15", "12:30",
                "15:00", "15:15", "15:30", "15:45", "16:00", "16:15", "16:30", "16:45",
                "17:00", "17:15", "17:30", "17:45", "18:00", "18:15", "18:30", "18:45",
                "19:00"
            ];
        } elseif ($dayOfWeek == 6) { // Sabato
            $times = [
                "08:00", "08:15", "08:30", "08:45", "09:00", "09:15", "09:30", "09:45",
                "10:00", "10:15", "10:30", "10:45", "11:00", "11:15", "11:30", "11:45",
                "12:00", "12:15", "12:30", "12:45", "13:00", "13:15", "13:30", "13:45",
                "14:00", "14:15", "14:30", "14:45", "15:00", "15:15", "15:30", "15:45",
                "16:00", "16:15", "16:30", "16:45", "17:00"
            ];
        }

        foreach ($times as $time) {
            echo "<div class='time-slot'><strong>$time</strong></div>";
        }

        echo "</div>"; // Chiusura di #calendar-grid
    }
    ?>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let selectedDate = "<?php echo $date; ?>"; 
        console.log("Data selezionata:", selectedDate);

        fetch(`getAppointments.php?date=${selectedDate}`)
            .then(response => response.json())
            .then(data => {
                console.log("Dati ricevuti:", data); // Debug
                if (data.error) {
                    console.error("Errore:", data.error);
                } else {
                    renderAppointments(data);
                }
            })
            .catch(error => console.error("Errore nel caricamento:", error));
    });

    function renderAppointments(appointments) {
        let container = document.getElementById("calendar-grid");

        appointments.forEach(appt => {
            let startTime = appt.startTime;
            let duration = appt.totalDuration;
            let position = getTimePosition(startTime);

            let appointmentDiv = document.createElement("div");
            appointmentDiv.classList.add("appointment");
            appointmentDiv.style.top = `${position}px`;
            appointmentDiv.style.height = `${duration * 2}px`;
            appointmentDiv.style.backgroundColor = getRandomColor();
            appointmentDiv.innerHTML = `<strong>${appt.customer}</strong><br>${appt.services.map(s => s.name).join(", ")}`;

            container.appendChild(appointmentDiv);
        });
    }

    function getTimePosition(time) {
        let [hours, minutes] = time.split(":").map(Number);
        let totalMinutes = (hours * 60) + minutes;
        let openingMinutes = (8 * 60); // Giornata inizia alle 08:00
        return (totalMinutes - openingMinutes) * 2; // Scala: 1px = 0.5 min
    }

    function getRandomColor() {
        const colors = ["#ff7f50", "#6495ed", "#ff69b4", "#ffa500", "#32cd32"];
        return colors[Math.floor(Math.random() * colors.length)];
    }
</script>

</body>
</html>

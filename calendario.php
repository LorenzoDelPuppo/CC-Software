<?php
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];

// Recupera il ruolo dell'utente dal database
$sql = "SELECT user_tipe FROM Customer WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();
$conn->close();

// Controllo di accesso: solo amministratore e operatrice possono accedere
if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="menu_profilo.js" defer></script>
    <link rel="stylesheet" href="style/barra_alta.css">
    <title>Calendario</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #ddd; text-align: center; padding: 10px; cursor: pointer; }
        .current-day { background-color: #4CAF50; color: white; }
        .nav { display: flex; justify-content: space-between; margin-bottom: 10px; }
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

    <div class="nav">
        <button onclick="prevMonth()">◀ Mese Precedente</button>
        <h2 id="month-year"></h2>
        <button onclick="nextMonth()">Mese Successivo ▶</button>
    </div>

    <table id="calendar">
        <thead>
            <tr>
                <th>Dom</th> <th>Lun</th> <th>Mar</th> <th>Mer</th> <th>Gio</th> <th>Ven</th> <th>Sab</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>

    <script>
        let currentDate = new Date();

        function renderCalendar() {
            let year = currentDate.getFullYear();
            let month = currentDate.getMonth();
            document.getElementById("month-year").textContent = currentDate.toLocaleDateString("it-IT", { month: "long", year: "numeric" });

            let firstDay = new Date(year, month, 1).getDay();
            let daysInMonth = new Date(year, month + 1, 0).getDate();

            let tbody = document.querySelector("#calendar tbody");
            tbody.innerHTML = "";
            let row = document.createElement("tr");

            for (let i = 0; i < firstDay; i++) {
                row.appendChild(document.createElement("td"));
            }

            for (let day = 1; day <= daysInMonth; day++) {
                let cell = document.createElement("td");
                cell.textContent = day;
                cell.onclick = () => selectDate(year, month + 1, day);
                
                if (day === new Date().getDate() && month === new Date().getMonth() && year === new Date().getFullYear()) {
                    cell.classList.add("current-day");
                }

                row.appendChild(cell);

                if ((firstDay + day) % 7 === 0) {
                    tbody.appendChild(row);
                    row = document.createElement("tr");
                }
            }

            if (row.children.length > 0) {
                tbody.appendChild(row);
            }
        }

        function selectDate(year, month, day) {
            let formattedDate = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            window.location.href = `blocchi_calendar.php?date=${formattedDate}`;
        }


        function prevMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }

        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }

        renderCalendar();
    </script>

</body>
</html>

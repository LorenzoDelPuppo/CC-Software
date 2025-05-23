<?php
session_start();
require_once __DIR__ . '/../connect.php';

// Verifica se l'utente è loggato
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
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
    <script src=".././js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href=".././style/style_calendario.css">
    <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
    <title>Calendario</title>

</head>
<?php include '.././view-get/barra.php'; ?>
<body>
<div class="main-container">
    <div class="calendar-container">
    <div class="nav">
    <button id="today-btn" onclick="goToToday()">Oggi</button>
    <div class="nav-controls">
        <button id="prev-month" onclick="prevMonth()">❮</button>
        <h2 id="month-year"></h2>
        <button id="next-month" onclick="nextMonth()">❯</button>
        
    </div>
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
            window.location.href = `.././view-get/blocchi_calendar.php?date=${formattedDate}`;
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

        function goToToday() {
    currentDate = new Date();
    renderCalendar();
}
        
    </script>
    </div>
    </div>

</body>
</html>

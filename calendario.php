<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendario</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        td, th { border: 1px solid #ddd; text-align: center; padding: 10px; cursor: pointer; }
        .current-day { background-color: #4CAF50; color: white; }
        .nav { display: flex; justify-content: space-between; margin-bottom: 10px; }
    </style>
</head>
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
            window.location.href = `dashboard.php?date=${formattedDate}`;
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

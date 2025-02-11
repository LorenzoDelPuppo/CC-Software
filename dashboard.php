<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <form action="logout.php" method="post">
        <button type="submit">Logout</button>
    </form>

    <?php
    session_start();
    require 'connect.php'; // Inclusione della connessione al database
    require 'showAll.php'; // Inclusione del file con la funzione showAllAppuntamenti

    // Mostra tutti gli appuntamenti nella dashboard
    echo "<br>";
    showAllAppuntamenti($conn);
    echo "<br>";
    $conn->close();
    ?>

    <div class="button-container" style="display: flex; gap: 10px;">
        <form action="prenotatest.php" method="post">
            <button type="submit">Aggiungi Appuntamento</button>
        </form>
        <form action="calendario.php" method="post">
            <button type="submit">Calendario</button>
        </form>
        <form action="clienti.php" method="post">
            <button type="submit">Gestione Clienti</button>
        </form>
        <form action="report.php" method="post">
            <button type="submit">Report</button>
        </form>
    </div>
</body>
</html>
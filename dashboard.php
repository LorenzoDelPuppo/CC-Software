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

    $customer_id = $_SESSION['customer_id'];
    echo "<h1>Benvenuto! Il tuo ID cliente è: " . $customer_id . "</h1>";
    
    // Mostra tutti gli appuntamenti nella dashboard
    showAllAppuntamenti($conn);
    ?>
</body>
</html>

<?php
$conn->close();
?>
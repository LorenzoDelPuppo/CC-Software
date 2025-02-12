<?php 
session_start();
require_once 'connect.php'; // Assicurati che questo file contenga la corretta configurazione per il DB

// Verifica che l'utente sia loggato: controlla se esiste il customer_id in sessione
if (!isset($_SESSION['customer_id'])) {
    // Se non esiste, reindirizza alla pagina di login o mostra un messaggio di errore
    header("Location: login.php");
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Query per recuperare gli appuntamenti prenotati dal cliente
// La query utilizza GROUP_CONCAT per unire in una stringa i nomi dei servizi relativi ad ogni appuntamento
$query = "SELECT a.dateTime, 
                 GROUP_CONCAT(sc.nameS SEPARATOR ', ') AS servizi
          FROM appointment a
          LEFT JOIN mergeAS mas ON a.appointment_id = mas.appointment_id
          LEFT JOIN serviceCC sc ON mas.service_id = sc.service_id
          WHERE a.customer_id = ?
          GROUP BY a.appointment_id
          ORDER BY a.dateTime DESC";

if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    die("Errore nella preparazione della query: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>I miei Appuntamenti</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2em;
        }
        h1 {
            text-align: center;
        }
        table {
            border-collapse: collapse;
            width: 80%;
            margin: auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 0.8em;
            text-align: center;
        }
        th {
            background-color: #eee;
        }
        .no-data {
            text-align: center;
            margin-top: 2em;
            font-size: 1.2em;
        }
        /* Stile per il pulsante per tornare al menu */
        .menu-button {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            font-size: 1.2em;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        .menu-button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>I miei Appuntamenti</h1>
    <?php if ($result->num_rows > 0): ?>
        <table>
            <tr>
                <th>Data e Ora</th>
                <th>Servizi Prenotati</th>
            </tr>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['dateTime']); ?></td>
                    <td><?php echo htmlspecialchars($row['servizi']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="no-data">Non hai prenotato nessun appuntamento.</p>
    <?php endif; ?>

    <!-- Pulsante per tornare al Menu -->
    <button class="menu-button" onclick="window.location.href='menu.php'">
        Torna al Menu
    </button>
</body>
</html>

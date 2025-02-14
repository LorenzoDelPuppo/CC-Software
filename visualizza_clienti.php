<?php
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato e ha i permessi
if (!isset($_SESSION['email']) || $_SESSION['user_tipe'] != 'amministratore') {
    header("Location: login.php");
    exit();
}

// Controllo se la connessione è attiva
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}

$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Recupera tutti i clienti (filtrati per nome se viene effettuata una ricerca)
$sql = "SELECT customer_id, fName, lName, phoneN, email, hair, gender, preference, wants_notification, user_tipe, nota 
        FROM Customer 
        WHERE fName LIKE ? OR lName LIKE ?";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Errore nella preparazione della query: " . $conn->error);
}

$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

if ($result === false) {
    die("Errore nell'esecuzione della query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestione Clienti</title>
    <link rel="stylesheet" href="style/style_input.css">
</head>
<body>
    <h1>Lista Clienti</h1>
    <form method="GET" action="visualizza_clienti.php">
        <input type="text" name="search" placeholder="Cerca per nome" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
        <button type="submit">Cerca</button>
    </form>
    <table border="1">
        <tr>
            <th>Nome</th>
            <th>Cognome</th>
            <th>Telefono</th>
            <th>Email</th>
            <th>Genere</th>
            <th>Capelli</th>
            <th>Modifica</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['fName']); ?></td>
                <td><?php echo htmlspecialchars($row['lName']); ?></td>
                <td><?php echo htmlspecialchars($row['phoneN']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                <td><?php echo htmlspecialchars($row['hair']); ?></td>
                <td><a href="modifica_cliente.php?customer_id=<?php echo $row['customer_id']; ?>">Modifica</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>

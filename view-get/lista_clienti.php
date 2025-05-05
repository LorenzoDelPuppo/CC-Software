<?php
session_start();

// Connessione al database
require_once __DIR__ . '/../connect.php';

// Se l'utente ha inviato un nuovo valore per la nota, aggiorniamo nel database
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nota']) && isset($_POST['customer_id'])) {
    $nota = $_POST['nota'];
    $customer_id = $_POST['customer_id'];

    // Prepara l'update della nota
    $sql = "UPDATE Customer SET nota = ? WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $nota, $customer_id);
    $stmt->execute();
    $stmt->close();
}

// Recupera tutti i clienti dal database
$sql = "SELECT customer_id, fName, lName, phoneN, nota FROM Customer";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Clienti</title>
    <link rel="stylesheet" href="../style/style_clienti.css">
</head>
<body>

<?php include '../view-get/barra.php'; ?>

<div class="client-container">

    <h1>Lista Clienti</h1>
    <div class="client-section">
        <h2>Ricerca Cliente</h2>
        <form method="GET" action="../view-get/visualizza_clienti.php" class="search-form">
            <input type="text" name="search" placeholder="Cerca per nome" value="<?php echo isset($_GET['search']) ? $_GET['search'] : ''; ?>">
            <button type="submit">Cerca</button>
        </form>
    </div>

    <div class="client-section">
        <h2>Elenco Clienti</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Telefono</th>
                        <th>Nota</th>
                        <th>Modifica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="clickable-row" data-href="./add-edit/modifica_cliente.php?id=<?php echo $row['customer_id']; ?>">
                                <td><?php echo htmlspecialchars($row['fName']); ?></td>
                                <td><?php echo htmlspecialchars($row['lName']); ?></td>
                                <td><?php echo htmlspecialchars($row['phoneN']); ?></td>
                                <td>
                                    <form action="lista_clienti.php" method="POST" class="nota-form">
                                        <input type="hidden" name="customer_id" value="<?php echo $row['customer_id']; ?>">
                                        <input type="text" name="nota" value="<?php echo htmlspecialchars($row['nota'] ?? ''); ?>" class="nota-input">
                                        <button type="submit" class="edit-btn">Salva Nota</button>
                                    </form>
                                </td>
                                <td>
                                    <a href="../add-edit/modifica_cliente.php?id=<?php echo $row['customer_id']; ?>" class="edit-btn">Modifica Tutto</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">Nessun cliente trovato.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</div>

<?php $conn->close(); ?>

</body>
</html>

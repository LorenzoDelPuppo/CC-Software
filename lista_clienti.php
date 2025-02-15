<?php
session_start();

// Controllo della sessione: verifica se l'utente è loggato usando l'email
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'connect.php';

$sql = "SELECT customer_id, fName, lName, phoneN, nota FROM Customer";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista Clienti</title>
    <link rel="stylesheet" href="style/style_input.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <div class="logo-container">
        <img src="style/rullino/logo.png" alt="Che Capelli Logo" class="logo">
    </div>
    <div class="content-container">
        <h2>Lista Clienti</h2>
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cognome</th>
                    <th>Telefono</th>
                    <th>Nota</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fName']); ?></td>
                            <td><?php echo htmlspecialchars($row['lName']); ?></td>
                            <td><?php echo htmlspecialchars($row['phoneN']); ?></td>
                            <td>
                                <!-- Form inline per aggiornare la nota e passare l'ID alla pagina di modifica -->
                                <form action="modifica_cliente.php" method="get">
                                    <input type="hidden" name="id" value="<?php echo $row['customer_id']; ?>">
                                    <input type="text" name="nota" value="<?php echo htmlspecialchars($row['nota']); ?>">
                                    <button type="submit">Modifica</button>
                                </form>
                            </td>
                            <td>
                                <!-- Link alternativo per modificare l'intero record -->
                                <a href="modifica_cliente.php?id=<?php echo $row['customer_id']; ?>">Modifica Tutto</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">Nessun cliente trovato.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php $conn->close(); ?>
</body>
</html>

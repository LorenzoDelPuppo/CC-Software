<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit();
}

$search = $_GET['search'] ?? '';
$clienteSelezionatoId = $_POST['selezionaUtente'] ?? null;
$clientiTrovati = [];
$clienteDettagli = null;

if (!empty($search)) {
    $like = "%" . $search . "%";
    $sql = "SELECT customer_id, fName, lName FROM Customer 
            WHERE fName LIKE ? OR lName LIKE ? OR CONCAT(fName, ' ', lName) LIKE ? OR email LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $like, $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $clientiTrovati[] = $row;
    }
    $stmt->close();
}

if ($clienteSelezionatoId) {
    $sql = "SELECT * FROM Customer WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $clienteSelezionatoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $clienteDettagli = $result->fetch_assoc();
    $stmt->close();

    // Recupera appuntamenti
    $sql_app = "SELECT appointment_id, dateTime FROM appointment WHERE customer_id = ? ORDER BY dateTime DESC";
    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->bind_param("i", $clienteSelezionatoId);
    $stmt_app->execute();
    $result_app = $stmt_app->get_result();

    $appuntamenti = [];
    while ($row = $result_app->fetch_assoc()) {
        $row['servizi'] = [];
        $sql_serv = "SELECT s.nameS FROM serviceCC s 
                     INNER JOIN mergeAS m ON s.service_id = m.service_id 
                     WHERE m.appointment_id = ?";
        $stmt_serv = $conn->prepare($sql_serv);
        $stmt_serv->bind_param("i", $row['appointment_id']);
        $stmt_serv->execute();
        $res_serv = $stmt_serv->get_result();
        while ($serv = $res_serv->fetch_assoc()) {
            $row['servizi'][] = $serv['nameS'];
        }
        $stmt_serv->close();
        $appuntamenti[] = $row;
    }
    $stmt_app->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Lista Clienti</title>
    <link rel="stylesheet" href="../style/style_clienti.css">
</head>
<body>
<?php include '../view-get/barra.php'; ?>
<div class="client-container">
    <h1>Lista Clienti</h1>
    <form method="get" action="">
        <label for="search">Ricerca Cliente</label>
        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Nome, Cognome o Email">
        <button type="submit">Cerca</button>
    </form>

    <?php if (!$clienteDettagli && !empty($clientiTrovati)): ?>
        <h2>Risultati della ricerca:</h2>
        <ul>
            <?php foreach ($clientiTrovati as $cliente): ?>
                <li>
                    <strong><?php echo htmlspecialchars($cliente['fName'] . ' ' . $cliente['lName']); ?></strong>
                    <form method="post" style="display:inline;">
                        <input type="hidden" name="selezionaUtente" value="<?php echo $cliente['customer_id']; ?>">
                        <button type="submit">Seleziona</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($clienteDettagli): ?>
        <h2>Dettagli Cliente</h2>
        <p><strong>Nome:</strong> <?php echo htmlspecialchars($clienteDettagli['fName']); ?></p>
        <p><strong>Cognome:</strong> <?php echo htmlspecialchars($clienteDettagli['lName']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($clienteDettagli['email']); ?></p>
        <p><strong>Telefono:</strong> <?php echo htmlspecialchars($clienteDettagli['phoneN']); ?></p>
        <p><strong>Capelli:</strong> <?php echo htmlspecialchars($clienteDettagli['hair']); ?></p>
        <p><strong>Genere:</strong> <?php echo htmlspecialchars($clienteDettagli['gender']); ?></p>
        <p><strong>Nota:</strong> <?php echo nl2br(htmlspecialchars($clienteDettagli['nota'] ?? '')); ?></p>

        <h3>Appuntamenti</h3>
        <?php if (!empty($appuntamenti)): ?>
            <ul>
                <?php foreach ($appuntamenti as $app): ?>
                    <li>
                        <strong><?php echo date('d/m/Y H:i', strtotime($app['dateTime'])); ?></strong><br>
                        Servizi: <?php echo empty($app['servizi']) ? 'Nessun servizio' : implode(', ', $app['servizi']); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Nessun appuntamento trovato.</p>
        <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>
<?php $conn->close(); ?>

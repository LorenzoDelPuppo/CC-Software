<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit();
}

$search = $_GET['search'] ?? '';
$clientiTrovati = [];

if (!$search) {
    echo "Nessun parametro di ricerca fornito.";
    exit;
}

$like = "%" . $search . "%";
$sql = "SELECT * FROM Customer 
        WHERE fName LIKE ? OR lName LIKE ? OR CONCAT(fName, ' ', lName) LIKE ? OR email LIKE ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $like, $like, $like, $like);
$stmt->execute();
$result = $stmt->get_result();

while ($cliente = $result->fetch_assoc()) {
    $customer_id = $cliente['customer_id'];

    // Recupera appuntamenti del cliente
    $sql_app = "SELECT a.appointment_id, a.dateTime FROM appointment a WHERE a.customer_id = ? ORDER BY a.dateTime DESC";
    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->bind_param("i", $customer_id);
    $stmt_app->execute();
    $result_app = $stmt_app->get_result();

    $appuntamenti = [];
    while ($row = $result_app->fetch_assoc()) {
        $appuntamento_id = $row['appointment_id'];
        $row['servizi'] = [];

        // Servizi per ogni appuntamento
        $sql_servizi = "
            SELECT s.nameS
            FROM serviceCC s
            INNER JOIN mergeAS m ON s.service_id = m.service_id
            WHERE m.appointment_id = ?";
        $stmt_serv = $conn->prepare($sql_servizi);
        $stmt_serv->bind_param("i", $appuntamento_id);
        $stmt_serv->execute();
        $result_serv = $stmt_serv->get_result();
        while ($serv = $result_serv->fetch_assoc()) {
            $row['servizi'][] = $serv['nameS'];
        }

        $appuntamenti[] = $row;
    }

    $cliente['appuntamenti'] = $appuntamenti;
    $clientiTrovati[] = $cliente;
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Clienti</title>
    <link rel="stylesheet" href="../style/style_clienti.css">
</head>
<body>
<?php include '../view-get/barra.php'; ?>

<div class="client-container">
    <h1>Risultati della Ricerca</h1>

    <?php if (empty($clientiTrovati)): ?>
        <p>Nessun cliente trovato.</p>
    <?php else: ?>
        <?php foreach ($clientiTrovati as $cliente): ?>
            <div class="cliente-box">
                <h2><?php echo htmlspecialchars($cliente['fName'] . ' ' . $cliente['lName']); ?></h2>
                <p><strong>Telefono:</strong> <?php echo htmlspecialchars($cliente['phoneN']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($cliente['email']); ?></p>
                <p><strong>Genere:</strong> <?php echo htmlspecialchars($cliente['gender']); ?></p>
                <p><strong>Capelli:</strong> <?php echo htmlspecialchars($cliente['hair']); ?></p>
                <p><strong>Nota:</strong> <?php echo nl2br(htmlspecialchars($cliente['nota'] ?? '')); ?></p>

                <h3>Appuntamenti</h3>
                <?php if (!empty($cliente['appuntamenti'])): ?>
                    <ul>
                        <?php foreach ($cliente['appuntamenti'] as $app): ?>
                            <li>
                                <strong><?php echo date('d/m/Y H:i', strtotime($app['dateTime'])); ?></strong><br>
                                Servizi: 
                                <?php echo empty($app['servizi']) ? 'Nessun servizio' : implode(', ', $app['servizi']); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>Nessun appuntamento trovato.</p>
                <?php endif; ?>
                <hr>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <a href="lista_clienti.php">← Torna alla lista clienti</a>
</div>

</body>
</html>

<?php $conn->close(); ?>

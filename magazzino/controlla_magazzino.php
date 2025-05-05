<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_SESSION['email'])) {
    header("Location: ../add-edit/login.php");
    exit();
}

if (!isset($_SESSION['customer_id'])) {
    $email = $_SESSION['email'];
    $query = "SELECT customer_id FROM Customer WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['customer_id'] = $row['customer_id'];
    } else {
        die("Errore: Cliente non trovato.");
    }
    $stmt->close();
}
?>

<?php
require_once __DIR__ . '/../connect.php';

$codice = $_GET['codice'] ?? '';

$stmt = $conn->prepare("SELECT nome_p, QTA FROM magazzino WHERE cod_p = ?");
$stmt->bind_param("s", $codice);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['trovato' => true, 'nome' => $row['nome_p'], 'qta' => $row['QTA']]);
} else {
    echo json_encode(['trovato' => false]);
}
?>

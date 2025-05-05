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

$data = json_decode(file_get_contents("php://input"), true);

$nome = $data['nome'];
$codice = $data['codice'];
$qta = (int)$data['qta'];

$stmt = $conn->prepare("INSERT INTO magazzino (nome_p, cod_p, QTA) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $nome, $codice, $qta);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
?>

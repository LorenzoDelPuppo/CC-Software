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
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['customer_id'] = $row['customer_id'];
        } else {
            die("Errore: Cliente non trovato.");
        }
        $stmt->close();
    } else {
        die("Errore nella preparazione della query.");
    }
}

require_once __DIR__ . '/../connect.php';
$data = json_decode(file_get_contents("php://input"), true);
$codice = $data['codice'];
$delta = isset($data['delta']) ? (int)$data['delta'] : 0;

if (isset($data['nuova_qta'])) {
    $nuova_qta = (int)$data['nuova_qta'];
    $stmt = $conn->prepare("UPDATE magazzino SET QTA = ? WHERE cod_p = ?");
    $stmt->bind_param("is", $nuova_qta, $codice);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("UPDATE magazzino SET QTA = QTA + ? WHERE cod_p = ?");
    $stmt->bind_param("is", $delta, $codice);
    $stmt->execute();
}

$stmt = $conn->prepare("SELECT QTA FROM magazzino WHERE cod_p = ?");
$stmt->bind_param("s", $codice);
$stmt->execute();
$result = $stmt->get_result();
$qta = $result->fetch_assoc()['QTA'];

echo json_encode(['success' => true, 'nuova_qta' => $qta]);
?>

<?php
session_start();
require_once __DIR__ . '/../connect.php';

if (!isset($_POST['codice'])) {
    echo json_encode(["error" => "Nessun codice ricevuto"]);
    exit;
}

$codice = trim($_POST['codice']);

$sql = "SELECT * FROM magazzino WHERE cod_p = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $codice);
$stmt->execute();
$result = $stmt->get_result();
$prodotto = $result->fetch_assoc();

if ($prodotto) {
    echo json_encode(["found" => true, "nome" => $prodotto['nome_p'], "quantita" => $prodotto['QTA']]);
} else {
    echo json_encode(["found" => false]);
}

$stmt->close();
$conn->close();
?>

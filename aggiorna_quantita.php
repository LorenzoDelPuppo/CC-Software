<?php
session_start();
require_once __DIR__ . '/../connect.php';

// Controllo accessi
if (!isset($_SESSION['email'])) {
    echo json_encode(["error" => "Non autorizzato"]);
    exit;
}

$email = $_SESSION['email'];
$sql = "SELECT user_tipe FROM Customer WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    echo json_encode(["error" => "Accesso negato"]);
    exit;
}

// Ricezione dati
if (!isset($_POST['codice']) || !isset($_POST['modifica'])) {
    echo json_encode(["error" => "Dati mancanti"]);
    exit;
}

$codice = trim($_POST['codice']);
$modifica = (int) $_POST['modifica'];

// Aggiorna la quantità
$sql = "UPDATE magazzino SET QTA = QTA + ? WHERE cod_p = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $modifica, $codice);
if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Errore nell'aggiornamento"]);
}

$stmt->close();
$conn->close();
?>

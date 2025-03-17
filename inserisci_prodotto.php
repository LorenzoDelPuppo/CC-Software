<?php
session_start();
require_once __DIR__ . '/../connect.php';

$codice = isset($_GET['codice']) ? trim($_GET['codice']) : "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = trim($_POST['nome']);
    $quantita = (int) $_POST['quantita'];

    if (!empty($nome) && $quantita > 0) {
        $sql = "INSERT INTO magazzino (nome_p, cod_p, QTA) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssi", $nome, $codice, $quantita);
        $stmt->execute();
        header("Location: successo.php");
        exit;
    }
}
?>

<form method="post">
    <input type="text" name="nome" placeholder="Nome Prodotto" required>
    <input type="number" name="quantita" placeholder="Quantità" min="1" required>
    <input type="hidden" name="codice" value="<?php echo htmlspecialchars($codice); ?>">
    <button type="submit">Aggiungi</button>
</form>

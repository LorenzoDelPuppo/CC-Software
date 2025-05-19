<?php
session_start();
require_once __DIR__ . '/../connect.php'; // Aggiorna percorso se serve

if (!isset($_SESSION['email'])) {
    header("Location: ../add-edit/login.php");
    exit();
}

// Recupero customer_id se manca
if (!isset($_SESSION['customer_id'])) {
    $email = $_SESSION['email'];
    $query = "SELECT customer_id FROM Customer WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $_SESSION['customer_id'] = $row['customer_id'];
    } else {
        die("Errore: Cliente non trovato.");
    }
    $stmt->close();
}

// Funzione redirect post/redirect/get
function redirectToSelf() {
    header("Location: magazzino_show.php");
    exit();
}

// ELIMINAZIONE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    
    // Prima, opzionale: cancella immagine fisica (se vuoi, per ora no)
    /*
    $stmtImg = $conn->prepare("SELECT img_path FROM magazzino WHERE prod_id = ?");
    $stmtImg->bind_param("i", $del_id);
    $stmtImg->execute();
    $resImg = $stmtImg->get_result();
    if ($resImg->num_rows > 0) {
        $rowImg = $resImg->fetch_assoc();
        if (!empty($rowImg['img_path']) && file_exists(__DIR__ . '/' . $rowImg['img_path'])) {
            unlink(__DIR__ . '/' . $rowImg['img_path']);
        }
    }
    $stmtImg->close();
    */

    $stmt = $conn->prepare("DELETE FROM magazzino WHERE prod_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    redirectToSelf();
}

// MODIFICA (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prod_id'])) {
    $prod_id = (int)$_POST['prod_id'];
    $nome = trim($_POST['nome']);
    $codice = trim($_POST['codice']);
    $qta = (int)$_POST['qta'];

    $error = '';
    $img_path_db = null;

    // Gestione upload immagine
    if (isset($_FILES['immagine']) && $_FILES['immagine']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $tmpName = $_FILES['immagine']['tmp_name'];
        $fileName = basename($_FILES['immagine']['name']);
        // Per sicurezza puoi aggiungere qui controllo estensione e mime-type
        $targetFile = $uploadDir . $fileName;

        // Per evitare sovrascritture puoi rinominare il file, es:
        $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileNameNew = uniqid('img_') . '.' . $fileExt;
        $targetFile = $uploadDir . $fileNameNew;

        if (move_uploaded_file($tmpName, $targetFile)) {
            $img_path_db = 'uploads/' . $fileNameNew;
        } else {
            $error = "Errore durante il caricamento dell'immagine.";
        }
    }

    if ($nome === '' || $codice === '' || $qta < 0) {
        $error = "Compila tutti i campi correttamente.";
    }

    if ($error === '') {
        if ($img_path_db !== null) {
            // aggiorno anche immagine
            $stmt = $conn->prepare("UPDATE magazzino SET nome_p = ?, cod_p = ?, QTA = ?, img_path = ? WHERE prod_id = ?");
            $stmt->bind_param("ssssi", $nome, $codice, $qta, $img_path_db, $prod_id);
        } else {
            // aggiorno senza cambiare immagine
            $stmt = $conn->prepare("UPDATE magazzino SET nome_p = ?, cod_p = ?, QTA = ? WHERE prod_id = ?");
            $stmt->bind_param("ssii", $nome, $codice, $qta, $prod_id);
        }
        if ($stmt->execute()) {
            $stmt->close();
            redirectToSelf();
        } else {
            $error = "Errore durante l'aggiornamento del prodotto.";
        }
    }

    if (!isset($edit_id)) $edit_id = $prod_id;
}

// Se clicchi modifica, mostra form su prodotto specifico
$edit_id = isset($_GET['edit']) && is_numeric($_GET['edit']) ? (int)$_GET['edit'] : (isset($edit_id) ? $edit_id : null);

// Prendo tutti i prodotti
$sql = "SELECT * FROM magazzino ORDER BY nome_p ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<title>Magazzino - Gestione Prodotti con Immagini</title>
<style>
    body { font-family: Arial, sans-serif; margin: 40px; background:#fafafa; }
    h1 { text-align:center; margin-bottom:30px; }
    table {
        border-collapse: collapse; width: 90%; margin: 0 auto 40px;
        box-shadow: 0 0 10px rgba(0,0,0,0.1); background: white;
    }
    th, td {
        border: 1px solid #ddd; padding: 12px 15px; text-align:center;
        vertical-align: middle;
    }
    th {
        background-color: #007bff; color: white; font-weight:600;
    }
    tr:hover { background:#f1f1f1; }
    a.action-link {
        color:#007bff; text-decoration:none; font-weight:bold;
    }
    a.action-link:hover { text-decoration:underline; }
    form.edit-form {
        max-width: 450px; margin: 0 auto 40px; padding: 20px;
        background: white; border-radius: 8px; box-shadow: 0 0 8px rgba(0,0,0,0.1);
        text-align:left;
    }
    form.edit-form label { display: block; margin-top: 15px; font-weight:bold; }
    form.edit-form input[type=text], form.edit-form input[type=number],
    form.edit-form input[type=file] {
        width: 100%; padding: 8px; margin-top:5px; box-sizing: border-box;
    }
    form.edit-form button {
        margin-top:20px; padding:10px; width: 100%;
        background: #007bff; color: white; border:none;
        font-weight: bold; cursor:pointer; border-radius:5px;
    }
    form.edit-form button:hover { background:#0056b3; }
    .error { color: red; text-align:center; margin-bottom:20px; }
    .logout-link {
        display: block; width: 100px; margin: 0 auto;
        text-align: center; background:#dc3545; color: white;
        padding: 10px 0; text-decoration:none; border-radius:5px;
        font-weight:bold;
    }
    .logout-link:hover { background:#c82333; }
    img.product-img {
        max-width: 100px; max-height: 100px;
        object-fit: contain;
        border: 1px solid #ccc;
        border-radius: 4px;
    }
</style>
<script>
    function confirmDelete(prodId) {
        if (confirm("Sei sicuro di voler eliminare questo prodotto?")) {
            window.location.href = "magazzino_show.php?delete=" + prodId;
        }
        return false;
    }
</script>
</head>
<body>

<h1>Gestione Prodotti in Magazzino</h1>

<?php if (!empty($error)): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($edit_id !== null):
    $stmt = $conn->prepare("SELECT * FROM magazzino WHERE prod_id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 0) {
        echo "<p class='error'>Prodotto non trovato per modifica.</p>";
        $edit_id = null;
    } else {
        $productToEdit = $res->fetch_assoc();
    }
    $stmt->close();
endif; ?>

<?php if ($edit_id !== null): ?>
<form method="post" class="edit-form" action="magazzino_show.php" enctype="multipart/form-data">
    <input type="hidden" name="prod_id" value="<?= $productToEdit['prod_id'] ?>" />

    <label for="nome">Nome Prodotto:</label>
    <input type="text" id="nome" name="nome" required value="<?= htmlspecialchars($productToEdit['nome_p']) ?>" />

    <label for="codice">Codice Prodotto:</label>
    <input type="text" id="codice" name="codice" required value="<?= htmlspecialchars($productToEdit['cod_p']) ?>" />

    <label for="qta">Quantità:</label>
    <input type="number" id="qta" name="qta" min="0" required value="<?= htmlspecialchars($productToEdit['QTA']) ?>" />

    <label for="immagine">Immagine Prodotto (lascia vuoto per mantenere quella attuale):</label>
    <input type="file" id="immagine" name="immagine" accept="image/*" />

    <?php if (!empty($productToEdit['img_path'])): ?>
        <p>Immagine attuale:</p>
        <img src="<?= htmlspecialchars($productToEdit['img_path']) ?>" alt="Immagine Prodotto" class="product-img" />
    <?php endif; ?>

    <button type="submit">Salva Modifiche</button>
</form>
<?php endif; ?>

<table>
    <thead>
        <tr>
            <th>ID Prodotto</th>
            <th>Immagine</th>
            <th>Nome Prodotto</th>
            <th>Codice Prodotto</th>
            <th>Quantità</th>
            <th>Modifica</th>
            <th>Elimina</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['prod_id']) ?></td>
                <td>
                    <?php if (!empty($row['img_path'])): ?>
                        <img src="<?= htmlspecialchars($row['img_path']) ?>" alt="<?= htmlspecialchars($row['nome_p']) ?>" class="product-img" />
                    <?php else: ?>
                        Nessuna immagine
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['nome_p']) ?></td>
                <td><?= htmlspecialchars($row['cod_p']) ?></td>
                <td><?= htmlspecialchars($row['QTA']) ?></td>
                <td><a class="action-link" href="magazzino_show.php?edit=<?= $row['prod_id'] ?>">Modifica</a></td>
                <td><a class="action-link" href="#" onclick="return confirmDelete(<?= $row['prod_id'] ?>)">Elimina</a></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center; font-style:italic;">Nessun prodotto presente in magazzino.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<a href="../add-edit/logout.php" class="logout-link">Logout</a>

</body>
</html>

<?php
$conn->close();
?>

<?php
session_start();
require_once 'connect.php';

// Verifica se l'utente è loggato e ha i permessi
if (!isset($_SESSION['email']) || $_SESSION['user_tipe'] != 'amministratore'|| $_SESSION['user_tipe'] != 'operatrice') {
    header("Location: login.php");
    exit();
}

// Verifica se l'ID cliente è passato nella query string
if (!isset($_GET['customer_id']) || empty($_GET['customer_id'])) {
    die("Cliente non trovato.");
}

$customer_id = intval($_GET['customer_id']);

// Recupera i dati del cliente da modificare
$sql = "SELECT * FROM Customer WHERE customer_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    // Popola il form con i dati del cliente
    $fName = $row['fName'];
    $lName = $row['lName'];
    $phoneN = $row['phoneN'];
    $email = $row['email'];
    $hair = $row['hair'];
    $gender = $row['gender'];
    $preference = $row['preference'];
    $wants_notification = $row['wants_notification'];
    $nota = $row['nota'];
} else {
    die("Cliente non trovato.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera i dati dal form
    $fName = $_POST['fName'];
    $lName = $_POST['lName'];
    $phoneN = $_POST['phoneN'];
    $email = $_POST['email'];
    $hair = $_POST['hair'];
    $gender = $_POST['gender'];
    $preference = $_POST['preference'];
    $wants_notification = isset($_POST['wants_notification']) ? 1 : 0;
    $nota = $_POST['nota'];

    // Aggiorna i dati del cliente
    $sql = "UPDATE Customer SET fName = ?, lName = ?, phoneN = ?, email = ?, hair = ?, gender = ?, preference = ?, wants_notification = ?, nota = ? WHERE customer_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssssi", $fName, $lName, $phoneN, $email, $hair, $gender, $preference, $wants_notification, $nota, $customer_id);

    if ($stmt->execute()) {
        echo "Dati aggiornati con successo!";
        header("Location: visualizza_clienti.php");
        exit();
    } else {
        echo "Errore nell'aggiornamento dei dati.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <script src="menu_profilo.js" defer></script>
    <link rel="stylesheet" href="style/barra_alta.css">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Cliente</title>
</head>
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href="menu.php">
      <img src="style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href="profilo.php" class="dropdown-item">Profilo</a>
    <a href="settings.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href="logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>
</div>
<body>
    <h1>Modifica Cliente</h1>
    <form method="POST" action="modifica_cliente.php?customer_id=<?php echo $customer_id; ?>">
        <label for="fName">Nome</label>
        <input type="text" id="fName" name="fName" value="<?php echo htmlspecialchars($fName); ?>" required>

        <label for="lName">Cognome</label>
        <input type="text" id="lName" name="lName" value="<?php echo htmlspecialchars($lName); ?>" required>

        <label for="phoneN">Telefono</label>
        <input type="text" id="phoneN" name="phoneN" value="<?php echo htmlspecialchars($phoneN); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>

        <label for="hair">Tipo di Capelli</label>
        <select id="hair" name="hair">
            <option value="lunghi" <?php echo $hair == 'lunghi' ? 'selected' : ''; ?>>Lunghi</option>
            <option value="corti" <?php echo $hair == 'corti' ? 'selected' : ''; ?>>Corti</option>
        </select>

        <label for="gender">Genere</label>
        <select id="gender" name="gender">
            <option value="maschio" <?php echo $gender == 'maschio' ? 'selected' : ''; ?>>Maschio</option>
            <option value="femmina" <?php echo $gender == 'femmina' ? 'selected' : ''; ?>>Femmina</option>
        </select>

        <label for="preference">Preferenza</label>
        <select id="preference" name="preference">
            <option value="Barbara" <?php echo $preference == 'Barbara' ? 'selected' : ''; ?>>Barbara</option>
            <option value="Giulia" <?php echo $preference == 'Giulia' ? 'selected' : ''; ?>>Giulia</option>
            <option value="Casuale" <?php echo $preference == 'Casuale' ? 'selected' : ''; ?>>Casuale</option>
        </select>

        <label for="wants_notification">Notifiche</label>
        <input type="checkbox" id="wants_notification" name="wants_notification" <?php echo $wants_notification == 1 ? 'checked' : ''; ?>>

        <label for="nota">Nota</label>
        <textarea id="nota" name="nota"><?php echo htmlspecialchars($nota); ?></textarea>

        <button type="submit">Aggiorna</button>
    </form>
</body>
</html>

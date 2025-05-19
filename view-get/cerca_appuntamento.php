<?php
session_start();
require_once __DIR__ . '/../connect.php';

// Controllo degli accessi: solo amministratore e operatrice possono modificare appuntamenti
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
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
    header("Location: access_denied.php");
    exit;
}

$searchTerm = "";
$appointments = [];
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $searchTermLike = "%" . $searchTerm . "%";
    // Cerca gli appuntamenti in base al nome o cognome del cliente
    $sql = "SELECT a.appointment_id, a.dateTime, CONCAT(c.fName, ' ', c.lName) AS cliente 
            FROM appointment a 
            JOIN Customer c ON a.customer_id = c.customer_id
            WHERE c.fName LIKE ? OR c.lName LIKE ? OR CONCAT(c.fName, ' ', c.lName) LIKE ?
            ORDER BY a.dateTime ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $searchTermLike, $searchTermLike, $searchTermLike);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
  <meta charset="UTF-8">
  <title>Cerca Appuntamento</title>
  <?php include '.././view-get/barra.php'; ?>
  <script src=".././js/menu_profilo.js" defer></script>
  <link rel="stylesheet" href=".././style/barra_alta.css">
  <link rel="stylesheet" href=".././style/style_cercaAppuntamenti.css">
</head>
<body>
<div class="container">
  <h1>Cerca Appuntamento</h1>
  <form class="search-form" method="GET" action="cerca_appuntamento.php">
    <input type="text" name="search" placeholder="Inserisci nome cliente" value="<?php echo htmlspecialchars($searchTerm); ?>" required>
    <button type="submit" class="btn">Cerca</button>
  </form>

  <?php if (!empty($appointments)): ?>
  <div class="table-container">
    <table>
      <thead>
        <tr>
          <th>ID Appuntamento</th>
          <th>Data/Ora</th>
          <th>Cliente</th>
          <th>Modifica</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($appointments as $appt): ?>
        <tr>
          <td><?php echo $appt['appointment_id']; ?></td>
          <td><?php echo $appt['dateTime']; ?></td>
          <td><?php echo $appt['cliente']; ?></td>
          <td>
            <form method="GET" action=".././add-edit/modifica_appuntamento.php">
              <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
              <button type="submit" class="edit-btn">Modifica</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <?php if(isset($_GET['search'])): ?>
      <p>Nessun appuntamento trovato per "<?php echo htmlspecialchars($searchTerm); ?>"</p>
    <?php endif; ?>
  <?php endif; ?>
</div>
</body>
</html>

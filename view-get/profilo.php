<?php 
session_start();
require_once __DIR__ . '/../connect.php'; // Includi qui il file di connessione al database

// Verifica che l'utente sia loggato: controlla se esiste il customer_id in sessione
if (!isset($_SESSION['customer_id'])) {
    header("Location: .././add-edit/login.php"); // reindirizza alla pagina di login se non è loggato
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Recupera i dati del profilo (escludendo customer_id, password e wants_notification)
$query = "SELECT fName, lName, hair, phoneN, gender, preference, email 
          FROM Customer 
          WHERE customer_id = ?";
if ($stmt = $conn->prepare($query)) {
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $profile = $result->fetch_assoc();
    } else {
        die("Errore: Cliente non trovato.");
    }
    $stmt->close();
} else {
    die("Errore nella preparazione della query: " . $conn->error);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="it">
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href=".././view-get/menu.php">
      <img src=".././style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>

  <div class="right-section">
  <div class="user-menu">
  <!-- Icona utente (o un'immagine) -->
  <span class="user-icon">&#128100;</span>
  
  <!-- Dropdown -->
  <div class="dropdown-menu">
    <a href=".././view-get/profilo.php" class="dropdown-item">Profilo</a>
    <a href=".././add-edit/impostazioni.php" class="dropdown-item">Impostazioni</a>
    <hr class="dropdown-separator">
    <a href=".././add-edit/logout.php" class="dropdown-item logout-item">Logout</a>
  </div>
</div>
</div>
</div>


<head>
  <meta charset="UTF-8">
  <script src=".././js/menu_profilo.js" defer></script>
  <link rel="stylesheet" href=".././style/barra_alta.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href=".././style/style_profilo.css">
  <title>Profilo Cliente</title>
</head>

<body>
  
  <div class="profile-container">
  <img src=".././style/rullino/fotodefault.png" alt="fotoprofilo" class="profile-pic">


  <h1>Il Mio Profilo</h1>
    <!-- Immagine del profilo -->
    

    <!-- Contenitore delle informazioni strutturate in "isolette" -->
    <div class="profile-details">
      <div class="profile-box">
        <h3>Nome</h3>
        <p><?php echo htmlspecialchars($profile['fName']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Cognome</h3>
        <p><?php echo htmlspecialchars($profile['lName']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Tipo di Capelli</h3>
        <p><?php echo htmlspecialchars($profile['hair']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Telefono</h3>
        <p><?php echo htmlspecialchars($profile['phoneN']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Genere</h3>
        <p><?php echo htmlspecialchars($profile['gender']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Preferenza</h3>
        <p><?php echo htmlspecialchars($profile['preference']); ?></p>
      </div>
      <div class="profile-box">
        <h3>Email</h3>
        <p><?php echo htmlspecialchars($profile['email']); ?></p>
      </div>
    </div>

    <!-- Pulsante di logout -->
    <button class="menu-button" onclick="window.location.href='.././add-edit/logout.php'">Logout</button>
  </div>
</body>
</html>

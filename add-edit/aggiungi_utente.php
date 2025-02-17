<?php
ob_start();
session_start();
require_once __DIR__ . '/../connect.php';
require_once '.././add-edit/cript.php';

// Blocco di controllo accessi: solo amministratore e operatrice possono accedere
if (!isset($_SESSION['email'])) {
    header("Location: .././add-edit/login.php");
    exit;
}

$email = $_SESSION['email'];
$sql = "SELECT user_tipe FROM Customer WHERE email = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Errore nella preparazione della query: " . $conn->error);
}
$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->bind_result($userType);
$stmt->fetch();
$stmt->close();

if ($userType !== 'amministratore' && $userType !== 'operatrice') {
    header("Location: access_denied.php");
    exit;
}

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera e pulisci i dati inviati dal form
    $firstName   = trim($_POST['fName']);
    $lastName    = trim($_POST['sName']);
    $phoneNumber = trim($_POST['phoneN']);
    $emailNew    = trim($_POST['email']); // Email del nuovo utente
    $password    = $_POST['password'];
    $hairType    = $_POST['lunghezzaCapelli']; // 'lunghi' oppure 'corti'
    $gender      = $_POST['gender'];           // 'maschio' o 'femmina'
    $userTypeNew = $_POST['user_tipe'];          // 'cliente', 'amministratore' o 'operatrice'

    // Validazione minima
    if(empty($firstName) || empty($lastName) || empty($emailNew) || empty($password)) {
        $error = "Compila tutti i campi obbligatori.";
    } else {
        // Hash della password
        $hashedPassword = hashPassword($password);

        // Query di inserimento
        $sqlInsert = "INSERT INTO Customer (fName, lName, phoneN, email, password, hair, gender, user_tipe)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sqlInsert)) {
            $stmt->bind_param("ssssssss", $firstName, $lastName, $phoneNumber, $emailNew, $hashedPassword, $hairType, $gender, $userTypeNew);
            if ($stmt->execute()) {
                $success = "Utente aggiunto con successo!";
            } else {
                $error = "Errore nell'inserimento: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error = "Errore nella preparazione della query: " . $conn->error;
        }
    }
    $conn->close();
}
ob_end_flush();
?>
<!DOCTYPE html>
<html lang="it">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Aggiungi Utente</title>
  <link rel="stylesheet" href=".././style/style_input.css">

  <div class="logo-container">
    <a href=".././view-get/menu.php">
      <img src=".././style/rullino/logo.png" alt="Logo" class="logo" />
    </a>
  </div>
</head>
<body>
  <div class="form-container">
    <!-- Visualizzazione dei messaggi -->
    <?php if (!empty($success)) : ?>
      <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action=".././add-edit/aggiungi_utente.php" method="post">
      <!-- Dati Anagrafici -->
      <label for="fName">Nome</label>
      <input type="text" id="fName" name="fName" placeholder="Inserisci" required>
      
      <label for="sName">Cognome</label>
      <input type="text" id="sName" name="sName" placeholder="Inserisci" required>
      
      <label for="phoneN">Numero di Telefono</label>
      <input type="tel" id="phoneN" name="phoneN" placeholder="Inserisci">
      
      <label for="email">Email</label>
      <input type="email" id="email" name="email" placeholder="Inserisci" required>
      
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Inserisci" required>
      
      <!-- Selezione del tipo di capelli con immagini -->
      <label for="lunghezzaCapelli">Capelli</label>
      <div class="buttons_select">
        <div class="radio_menu">
          <input type="radio" id="lunghi" name="lunghezzaCapelli" value="lunghi" required>
          <label for="lunghi" class="img_label">
            <img src=".././style/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Capelli Lunghi">
          </label>
          <span>Lunghi</span>
        </div>
        <div class="radio_menu">
          <input type="radio" id="corti" name="lunghezzaCapelli" value="corti" required>
          <label for="corti" class="img_label">
            <img src=".././style/rullino/capellicorti.png" class="img_sceltacapelli" alt="Capelli Corti">
          </label>
          <span>Corti</span>
        </div>
      </div>
      
      <!-- Selezione del genere -->
      <label for="gender">Genere</label>
      <select id="gender" name="gender" required>
        <option value="maschio">Maschio</option>
        <option value="femmina">Femmina</option>
      </select>
      
      <!-- Selezione del tipo di utente per il nuovo account -->
      <label for="user_tipe">Tipo di Utente</label>
      <select id="user_tipe" name="user_tipe" required>
        <option value="cliente">Cliente</option>
        <option value="amministratore">Amministratore</option>
        <option value="operatrice">Operatrice</option>
      </select>
      
      <!-- Pulsante per aggiungere l'utente -->
      <button type="submit">Aggiungi</button>
    </form>
  </div>
</body>
</html>

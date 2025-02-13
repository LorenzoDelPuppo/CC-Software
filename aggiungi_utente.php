<?php
ob_start();
require_once 'connect.php';
require_once 'cript.php';

$success = "";
$error   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recupera e pulisci i dati inviati dal form
    $firstName   = trim($_POST['fName']);
    $lastName    = trim($_POST['sName']);
    $phoneNumber = trim($_POST['phoneN']);
    $email       = trim($_POST['email']);
    $password    = $_POST['password'];
    $hairType    = $_POST['lunghezzaCapelli']; // 'lunghi' oppure 'corti'
    $gender      = $_POST['gender'];           // 'maschio' o 'femmina'
    $userType    = $_POST['user_tipe'];          // 'cliente', 'amministratore' o 'operatrice'

    // Validazione minima
    if(empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
        $error = "Compila tutti i campi obbligatori.";
    } else {
        // Hash della password
        $hashedPassword = hashPassword($password);

        // Query di inserimento
        $sql = "INSERT INTO Customer (fName, lName, phoneN, email, password, hair, gender, user_tipe)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssssssss", $firstName, $lastName, $phoneNumber, $email, $hashedPassword, $hairType, $gender, $userType);
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
  <link rel="stylesheet" href="style/style_input.css">
  <style>
    /* Stili per la selezione dei capelli con immagini */
    .buttons_select {
        display: flex;
        gap: 20px;
        margin-bottom: 10px;
    }
    .radio_menu {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .img_label {
        cursor: pointer;
    }
    .img_sceltacapelli {
        width: 100px;
        height: auto;
        border: 2px solid transparent;
        transition: border-color 0.3s;
    }
    /* Evidenzia l'immagine quando viene selezionata */
    input[type="radio"]:checked + .img_label img {
        border-color: #007BFF;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <!-- Mostra eventuali messaggi -->
    <?php if (!empty($success)) : ?>
      <p class="success"><?php echo htmlspecialchars($success); ?></p>
    <?php endif; ?>
    <?php if (!empty($error)) : ?>
      <p class="error"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <form action="aggiungi_utente.php" method="post">
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
            <img src="style/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Capelli Lunghi">
          </label>
          <span>Lunghi</span>
        </div>
        <div class="radio_menu">
          <input type="radio" id="corti" name="lunghezzaCapelli" value="corti" required>
          <label for="corti" class="img_label">
            <img src="style/rullino/CapelliCorti.png" class="img_sceltacapelli" alt="Capelli Corti">
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
      
      <!-- Selezione del tipo di utente -->
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

<?php
session_start();
// Verifica che l'utente sia autenticato e che il ruolo sia amministratore o operatrice
if (!isset($_SESSION['user_tipe']) || ($_SESSION['user_tipe'] != 'amministratore' && $_SESSION['user_tipe'] != 'operatrice')) {
    header("Location: .././add-edit/login.php");
    exit();
}

require_once __DIR__ . '/../connect.php';
require_once '.././add-edit/cript.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Verifica che l'ID del cliente sia stato passato
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $customer_id = $_GET['id'];
        $sql = "SELECT * FROM Customer WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $customer = $result->fetch_assoc();
            // Se viene passato anche un valore per la nota (da .././view-get/lista_clienti.php) lo sovrascrive
            if (isset($_GET['nota'])) {
                $customer['nota'] = $_GET['nota'];
            }
        } else {
            echo "Cliente non trovato.";
            exit();
        }
        $stmt->close();
    } else {
        echo "ID cliente mancante.";
        exit();
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Elaborazione del form per aggiornare il cliente
    $customer_id = $_POST['customer_id'];
    $fName       = $_POST['fName'];
    $lName       = $_POST['lName'];
    $phoneN      = $_POST['phoneN'];
    $email       = $_POST['email'];
    $hair        = $_POST['hair'];       // atteso "lunghi" o "corti"
    $gender      = $_POST['gender'];     // atteso "maschio" o "femmina"
    $nota        = $_POST['nota'];

    // Se viene fornita una nuova password la aggiorniamo; altrimenti la lasciamo invariata
    $password = $_POST['password'];
    if (!empty($password)) {
        $hashedPassword = hashPassword($password);
        $sql = "UPDATE Customer 
                SET fName = ?, lName = ?, phoneN = ?, email = ?, hair = ?, gender = ?, nota = ?, password = ?
                WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $fName, $lName, $phoneN, $email, $hair, $gender, $nota, $hashedPassword, $customer_id);
    } else {
        $sql = "UPDATE Customer 
                SET fName = ?, lName = ?, phoneN = ?, email = ?, hair = ?, gender = ?, nota = ?
                WHERE customer_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $fName, $lName, $phoneN, $email, $hair, $gender, $nota, $customer_id);
    }

    if ($stmt->execute()) {
        echo "Cliente aggiornato con successo!";
        header("Location: .././view-get/lista_clienti.php");
        exit();
    } else {
        echo "Errore durante l'aggiornamento.";
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <script src=".././js/menu_profilo.js" defer></script>
    <link rel="stylesheet" href=".././style/barra_alta.css">
    
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifica Cliente</title>
    <link rel="stylesheet" href=".././style/style_input.css">
</head>
<div class="top-bar">
  <div class="left-section">
  </div>
  <div class="center-section">
    <a href=".././view-get/menu.php">
      <img src="style/rullino/rullino/logo.png" alt="Logo" class="logo" />
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
<body>
    <div class="logo-container">
        <img src="style/rullino/rullino/logo.png" alt="Che Capelli Logo" class="logo">
    </div>
    <div class="form-container">
        <h2>Modifica Cliente</h2>
        <form action=".././add-edit/modifica_cliente.php" method="post">
            <!-- Campo nascosto per l'ID del cliente -->
            <input type="hidden" name="customer_id" value="<?php echo isset($customer['customer_id']) ? $customer['customer_id'] : $customer_id; ?>">
            
            <label for="fName">Nome</label>
            <input type="text" id="fName" name="fName" placeholder="Inserisci nome" 
                   value="<?php echo isset($customer['fName']) ? htmlspecialchars($customer['fName']) : ''; ?>" required>
            
            <label for="lName">Cognome</label>
            <input type="text" id="lName" name="lName" placeholder="Inserisci cognome" 
                   value="<?php echo isset($customer['lName']) ? htmlspecialchars($customer['lName']) : ''; ?>" required>
            
            <label for="phoneN">Numero di Telefono</label>
            <input type="tel" id="phoneN" name="phoneN" placeholder="Inserisci numero" 
                   value="<?php echo isset($customer['phoneN']) ? htmlspecialchars($customer['phoneN']) : ''; ?>" required>
            
            <label for="email">Email</label>
            <input type="email" id="email" name="email" placeholder="Inserisci email" 
                   value="<?php echo isset($customer['email']) ? htmlspecialchars($customer['email']) : ''; ?>" required>
            
            <label for="password">Password (lascia vuoto per non cambiare)</label>
            <input type="password" id="password" name="password" placeholder="Inserisci nuova password">
            
            <label>Capelli</label>
            <div class="buttons_select">
                <div class="radio_menu">
                    <input type="radio" name="hair" id="lunghi" value="lunghi" 
                        <?php if((isset($customer['hair']) && $customer['hair'] == 'lunghi')) echo 'checked'; ?>>
                    <label for="lunghi" class="img_label">
                        <img src="style/rullino/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Lunghi">
                    </label>
                </div>
                <div class="radio_menu">
                    <input type="radio" name="hair" id="corti" value="corti" 
                        <?php if((isset($customer['hair']) && $customer['hair'] == 'corti')) echo 'checked'; ?>>
                    <label for="corti" class="img_label">
                        <img src="style/rullino/rullino/capellicorti.png" class="img_sceltacapelli" alt="Corti">
                    </label>
                </div>
            </div>
            
            <label for="gender">Genere</label>
            <select id="gender" name="gender">
                <option value="maschio" <?php if((isset($customer['gender']) && $customer['gender'] == 'maschio')) echo 'selected'; ?>>Maschio</option>
                <option value="femmina" <?php if((isset($customer['gender']) && $customer['gender'] == 'femmina')) echo 'selected'; ?>>Femmina</option>
            </select>
            
            <label for="nota">Nota</label>
            <textarea id="nota" name="nota" placeholder="Inserisci una nota"><?php echo isset($customer['nota']) ? htmlspecialchars($customer['nota']) : ''; ?></textarea>
            
            <button type="submit">Aggiorna Cliente</button>
        </form>
    </div>
</body>
</html>

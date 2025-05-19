<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/cript.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $firstName = $_POST['fName'];
    $lastName = $_POST['sName'];
    $phoneNumber = $_POST['phoneN'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hairType = $_POST['lunghezzaCapelli'];
    $gender = $_POST['gender'];

    $hashedPassword = hashPassword($password);

    $sql = "INSERT INTO Customer (fName, lName, phoneN, email, password, hair, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ Errore prepare(): " . $conn->error);
    }

    $stmt->bind_param('sssssss', $firstName, $lastName, $phoneNumber, $email, $hashedPassword, $hairType, $gender);

    if ($stmt->execute()) {
        header("Location: ../add-edit/login.php");
        exit();
    } else {
        echo "❌ Errore salvataggio dati: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <link rel="icon" href=".././style/rullino/icon.png" type="image/png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="descrizione pagina">
    <meta name="keywords" content="HTML, CSS, JavaScript, Tutorial">
    <meta name="author" content="CC-Softwer">
    <title>Che Capelli</title>
    <script src=".././js/app.js"></script>
    <link rel="stylesheet" href=".././style/style_input.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <meta name="robots" content="index, follow">
</head>
<body>
<div class="logo-container">
    <a href=".././view-get/menu.php">
        <img src=".././style/rullino/logo.png" alt="Che Capelli Logo" class="logo">
    </a>
</div>

<div class="form-container">
    <br>
    <form method="post" action="">
        <label for="fName">Nome</label>
        <input type="text" id="fName" name="fName" placeholder="Inserisci" required>

        <label for="sName">Cognome</label>
        <input type="text" id="sName" name="sName" placeholder="Inserisci" required>

        <label for="phoneN">Numero di Telefono</label>
        <input type="tel" id="phoneN" name="phoneN" placeholder="Inserisci" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Inserisci" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Inserisci" required>

        <label for="gender">Genere</label>
        <select id="gender" name="gender" required>
            <option value="maschio">Uomo</option>
            <option value="femmina">Donna</option>
        </select>

        <label for="hair">Capelli</label>
        <div class="buttons_select">
            <div class="radio_menu">
                <input type="radio" name="lunghezzaCapelli" id="lunghi" value="lunghi" required>
                <label for="lunghi" class="img_label">
                    <img src=".././style/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Lunghi">
                </label>
            </div>
            <div class="radio_menu">
                <input type="radio" name="lunghezzaCapelli" id="corti" value="corti">
                <label for="corti" class="img_label">
                    <img src=".././style/rullino/CapelliCorti.png" class="img_sceltacapelli" alt="Corti">
                </label>
            </div>
        </div>
        <br>
        <button type="submit">Registrati</button>
    </form>
</div>
</body>
</html>

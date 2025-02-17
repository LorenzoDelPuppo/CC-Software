<!DOCTYPE html>
<html lang="it">
<head>
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


    <div class="form-container">  <!-- classe per il contenitore -->
    <br>
        <form action=".././add-edit/index.php" method="post">

              <!-- Campo di input per i dati -->
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

            <!-- Menu a tendina per selezionare il genere -->
            <div>
                <label for="gender">Genere</label>
                <select id="gender" name="gender">
                    <option value="men">Uomo</option>
                    <option value="woman">Donna</option>
                    <option value="other">Preferisco non dirlo</option>
                </select>
            </div>
        
            <label for="hair">Capelli</label>

            <div class="buttons_select"> <!--pulsanti "radio" per la selezione dei capelli-->
                <div class="radio_menu">
                    <input type="radio" name="lunghezzaCapelli" id="lunghi" value="long">
                    <label for="lunghi" class="img_label">
                    <img src=".././style/rullino/capelliLunghi.png" class="img_sceltacapelli" alt="Lunghi">
                    </label>
                </div>
                <div class="radio_menu">
                    <input type="radio" name="lunghezzaCapelli" id="corti" value="short">
                    <label for="corti" class="img_label">
                    <img src=".././style/rullino/capellicorti.png" class="img_sceltacapelli" alt="Corti">
                    </label>
                </div>
            </div>
            <br>
                <button type="submit">Registrati</button>
        </div>
</body>
</html>

<?php
require_once __DIR__ . '/../connect.php';
require_once __DIR__ . '/cript.php';


//richiesta http post
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    //crazione delle variabili per memorizzarele con il post
    $firstName = $_POST['fName'];
    $lastName = $_POST['sName'];
    $phoneNumber = $_POST['phoneN'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hairType = $_POST['lunghezzaCapelli'];
    $gender = $_POST['gender'];

    //hashing delle password
    $hashedPassword = hashPassword($password);

    //query inserimento
    $sql = "INSERT INTO customer (fName, lName, phoneN, email, password, hair, gender)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    //connessione 
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss', $firstName, $lastName, $phoneNumber, $email, $hashedPassword, $hairType, $gender);

    if ($stmt->execute()) {
        echo "Dati salvati con successo!";
        header("location: .././add-edit/login.php");
        exit();
    } else {
        echo "Errore nel salvataggio dei dati.";
    }

    $stmt->close();
    $conn->close();
    
}
?>
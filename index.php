<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Una breve descrizione del contenuto della pagina.">
    <meta name="keywords" content="HTML, CSS, JavaScript, Tutorial">
    <meta name="author" content="Il tuo nome o nome dell'azienda">
    <title>Che Capelli</title>
    <script src="app.js"></script>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <meta name="robots" content="index, follow">
</head>
<body>
    <div class= "logo-cointainer">
        <h1 class="logo">Che capelli Feltrin (logo)</h1>
    </div>
    <br>
    <div class="form-container">
        <form action="index.php" method="post">
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
 
            <div class="dropdown">
                <label for="gender">Genere</label>
                <select id="gender" name="gender">
                    <option value="men">Uomo</option>
                    <option value="woman">Donna</option>
                    <option value="other">Preferisco non dirlo</option>
                </select>
            </div>
        
            <label for="hair">Capelli</label>

            <div class="buttons_select">
                <div class="radio_menu">
                    <input type="radio" name="lunghezzaCapelli" id="lunghi" value="long">
                    <label for="lunghi" class="img_label">
                    <img src="capelliLunghi.png" class="img_sceltacapelli" alt="Lunghi">
                    </label>
                </div>
                <div class="radio_menu">
                    <input type="radio" name="lunghezzaCapelli" id="corti" value="short">
                    <label for="corti" class="img_label">
                    <img src="CapelliCorti.png" class="img_sceltacapelli" alt="Corti">
                    </label>
                </div>
            </div>


            <br>

                <button type="submit">Invia</button>
        </div>
</body>
</html>

<?php
require_once 'connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $firstName = $_POST['fName'];
    $lastName = $_POST['sName'];
    $phoneNumber = $_POST['phoneN'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hairType = $_POST['lunghezzaCapelli'];
    $gender = $_POST['gender'];

    $sql = "INSERT INTO customer (fName, lName, phoneN, email, password, hair, gender)
            VALUES (?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss', $firstName,$lastName,$phoneNumber,$email,$password,$hairType,$gender);

    if ($stmt->execute()) {
        echo "Dati salvati con successo!";
    } else {
        echo "Errore nel salvataggio dei dati.";
    }
}
?>
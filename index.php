<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Una breve descrizione del contenuto della pagina.">
    <meta name="keywords" content="HTML, CSS, JavaScript, Tutorial">
    <meta name="author" content="Il tuo nome o nome dell'azienda">
    <title>che capelli</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <meta name="robots" content="index, follow">
</head>
<body>
<form action="index.php" method="post">
        <label for="fName">Nome:</label>
        <input type="text" id="fName" name="fName" placeholder="Inserisci il tuo nome" required>

        <label for="sName">Cognome:</label>
        <input type="text" id="sName" name="sName" placeholder="Inserisci il tuo cognome" required>
         
        <label for="phoneN">phoneN:</label>
        <input type="tel" id="phoneN" name="phoneN" placeholder="Inserisci il numero di telefono" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" placeholder="Inserisci la tua email" required>
        
        <label for="password">password:</label>
        <input type="password" id="password" name="password" placeholder="Inserisci la password" required>

            <label for="hair">capelli:</label>
        <select id="hair" name="hair" required>
            <option value="short">corti</option>
            <option value="long">lunghi</option>
        </select>

        <label for="gender">genere:</label>
        <select id="gender" name="gender" required>
            <option value="men">uomo</option>
            <option value="woman">donna</option>
        </select>
      
        <button type="submit">Invia</button>
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
    $hairType = $_POST['hair'];
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

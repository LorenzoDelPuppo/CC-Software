<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Che Capelli</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="logo-container">
    <img src="rullino/logo.png" alt="Che Capelli Logo" class="logo">
</div>
    <div class = "form-container">
        <h2>Login</h2>
        <form action="<?php echo $_SERVER["PHP_SELF"];?>" method="POST">
            <label for="inserisci l'email">Email</label>
            <input type="email" name="email" required>
            <br>
            <label for="inserisci la password">Password</label>
            <input type="password" name="password" required>
            <br>
          
            <button type="submit">Accedi</button>
        </form>
    </div>
</body>
</html>

<?php

session_start(); // Avvia la sessione

// Se l'utente è già loggato, reindirizza alla dashboard
if (isset($_SESSION['email'])) {
    header("Location: dashboard.php");
    exit();
}

require_once "connect.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){
    //recupero dati dal form 
    $email = $_POST["email"];
    $password = $_POST["password"];

    //query per l'estrazione dei dati 
    $query = "SELECT * FROM customer WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifica la password
        if ($password == $user["password"]) {
            // Salva l'email dell'utente nella sessione
            $_SESSION['email'] = $email;

            // Reindirizza alla dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            echo "Password errata.";
        }
    } else {
        echo "Email non trovata.";
    }

    $stmt->close();
    $conn->close();
}


?>
<?php
require_once __DIR__ . '/../connect.php'; // Collegamento al database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // Controllo se l'email esiste nel database
    $stmt = $conn->prepare("SELECT customer_id FROM Customer WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // L'email esiste, quindi genero un token
        $token = bin2hex(random_bytes(32));  // Genera un token sicuro

        // Link per il reset della password
        $reset_link = "https://checapelli.ittvive.it/add-edit/cambio_password_email.php?token=" . $token;

        // Invia l'email con il link di reset
        require_once __DIR__ . '/../email_recupero_password.php';  // Include il file per inviare l'email
        sendPasswordResetEmail($email, $reset_link);  // Funzione che invia l'email

        echo "Controlla la tua email per il link di reimpostazione della password.";
    } else {
        echo "Email non trovata.";
    }
}
?>

<link rel="stylesheet" href="../style/style_input.css">
<link rel="icon" href=".././style/rullino/icon.png" type="image/png">

<div class="logo-container">
    <a href="../view-get/menu.php">
        <img src="../style/rullino/logo.png" alt="Che Capelli Logo" class="logo">
    </a>
</div>

<div class="form-container">
    <h2>Password Dimenticata?</h2>
    <div class="logo-container">
        <img src="../style/rullino/password.png" alt="Logo">   
    </div>
    <label>Inserisci il tuo indirizzo email Che Capelli qui sotto. Ti invieremo via email i dettagli per la reimpostazione della tua password.</label>
    <form method="post">
        <input type="email" name="email" placeholder="Inserisci la tua email" required>
        <button type="submit">Invia</button>
    </form>
</div>

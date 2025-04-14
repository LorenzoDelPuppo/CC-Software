<?php
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    echo "✅ FORM RICEVUTO!<br>";
    print_r($_POST);
}
?>

<form method="post">
    <input type="text" name="nome" placeholder="nome">
    <button type="submit">Invia</button>
</form>

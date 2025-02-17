<?php

    session_start(); // Avvia la sessione su ogni pagina che include questo file

    // Funzione per verificare se l'utente è autenticato
    function check_login() {
        if (!isset($_SESSION['email'])) {
            header("Location: .././add-edit/login.php"); // Reindirizza alla pagina di login se non è loggato
        exit;
        }
    }

?>

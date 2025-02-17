<?php
// Funzione per generare un hash sicuro della password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Funzione per verificare una password rispetto all'hash salvato nel DB
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>

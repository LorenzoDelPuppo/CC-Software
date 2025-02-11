<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <form action="logout.php" method="post">
    <button type="submit">Logout</button>
    </form>
</head>

<?php
session_start();

$customer_id = $_SESSION['customer_id'];

echo "Benvenuto! Il tuo ID cliente è: " . $customer_id;
?>  
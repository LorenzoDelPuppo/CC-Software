<?php
session_start();


$customer_id = $_SESSION['customer_id'];

echo "Benvenuto! Il tuo ID cliente è: " . $customer_id;
?>  
<?php

    $host = 'localhost';
    $dbname = 'u482179263_checapelli'; 
    $username = 'root';      
    $password = '';          

    
        $conn = new mysqli($host,$username,$password,$dbname);

    if ($conn->connect_error) { 
        die('errore di con' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
?>

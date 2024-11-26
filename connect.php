<?php

    $host = 'localhost';
    $dbname = 'db_cc'; 
    $username = 'root';      
    $password = '';          

    
        $conn = new mysqli($host,$username,$password,$dbname);
    if ($conn->connect_error) { 
        die('errore di con' . $conn->connect_error);
    }
?>

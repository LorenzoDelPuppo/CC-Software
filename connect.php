<?php

    //$host = '193.203.168.54';
    $host = 'localhost';
    $dbname = 'u482179263_checapelli'; 
    //$username = 'u482179263_checapelli';      
    $username = 'root';      
    //$password = 'K+0>K44q';          
    $password = 'cWZ8Xr90d1mx';          

    
        $conn = new mysqli($host,$username,$password,$dbname);

    if ($conn->connect_error) { 
        die('errore di con' . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");
?>

<?php

$servername = "localhost";
$username = "user";
$password = "pass";


try {
    $pdo = new PDO(
       "mysql:host=$servername;dbname=theatre_booking",
        $username,
        $password,
[PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false]);

        // TODO: Write Post logic
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage(); 
}

?>
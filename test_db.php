<?php
try {
    $dsn = "mysql:host=127.0.0.1;port=3306;dbname=accounts";
    $user = "root";
    $pass = "";
    $pdo = new PDO($dsn, $user, $pass);
    echo "Connection successful!";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

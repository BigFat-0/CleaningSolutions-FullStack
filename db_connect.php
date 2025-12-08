<?php
// v1/db_connect.php

$host = 'h880oo80sksccwgggk8okwkk';
$db   = 'default';
$user = 'mysql';
$pass = 'SO16QPNRh3FeSHnDCsTXHIcXMjHDXwTXELh3kZ5FWN2vP1GB7IIpVtBHiXfQX10M';
$port = "3306";
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;port=$port;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // In a production env, you might log this instead of echoing
    die("Database Connection Failed: " . $e->getMessage());
}
?>
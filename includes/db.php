<?php
$host = 'localhost';
$db   = 'urenregistratie';
$user = 'root';       // of je hosting-gebruiker
$pass = '';           // wachtwoord indien nodig
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database verbinding mislukt: ' . $e->getMessage());
}

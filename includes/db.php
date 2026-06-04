<?php
$host     = getenv('MYSQLHOST')     ?: getenv('DB_HOST')     ?: '127.0.0.1';
$port     = getenv('MYSQLPORT')     ?: getenv('DB_PORT')     ?: '3306';
$dbname   = getenv('MYSQLDATABASE') ?: getenv('DB_NAME')     ?: 'priced2go_db';
$username = getenv('MYSQLUSER')     ?: getenv('DB_USER')     ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

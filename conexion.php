<?php
$host = getenv('MYSQLHOST') ?: 'localhost';
$port = getenv('MYSQLPORT') ?: '3306';
$dbname = getenv('MYSQLDATABASE') ?: 'acarez_logistica';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';

$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Error de conexión: ' . $conn->connect_error]));
}
?>

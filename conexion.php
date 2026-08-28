<?php
// Cargar variables de entorno del servidor
$host     = getenv('MYSQLHOST') ?: 'localhost';
$port     = getenv('MYSQLPORT') ?: '3306';
$dbname   = getenv('MYSQLDATABASE') ?: 'acarez_logistica';
$user     = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQLPASSWORD') ?: '';

// Crear conexión MySQLi
$conn = new mysqli($host, $user, $password, $dbname, (int)$port);

// Validar fallos de conexión
if ($conn->connect_error) {
    http_response_code(500);
    header('Content-Type: application/json');
    die(json_encode([
        'success' => false,
        'mensaje' => 'Error de conexión a la base de datos: ' . $conn->connect_error
    ]));
}

// 1. OBLIGATORIO: Establecer caracteres UTF-8 (acentos, ñ, símbolos)
$conn->set_charset("utf8mb4");

// 2. Establecer zona horaria en PHP y en la sesión de MySQL (-06:00 para México)
date_default_timezone_set('America/Mexico_City');
$conn->query("SET time_zone = '-06:00'");
?>

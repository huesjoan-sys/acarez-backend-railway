<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'acarez_logistica');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Error de conexión']);
    exit;
}

// Contar el total de viajes registrados
$result = $conn->query("SELECT COUNT(*) as total FROM viajes");
$total = $result->fetch_assoc()['total'];

echo json_encode(['total' => $total]);

$conn->close();
?>

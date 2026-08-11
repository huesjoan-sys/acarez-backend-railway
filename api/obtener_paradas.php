<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_GET['ruta_id'] ?? 0);

if ($ruta_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no válida']);
    exit;
}

$sql = "SELECT p.*, d.razon_social, d.sucursal 
        FROM paradas p 
        LEFT JOIN destinos d ON p.destino_id = d.id 
        WHERE p.ruta_id = ? 
        ORDER BY p.orden ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ruta_id);
$stmt->execute();
$result = $stmt->get_result();

$paradas = [];
while ($row = $result->fetch_assoc()) {
    $paradas[] = $row;
}

echo json_encode(['success' => true, 'paradas' => $paradas]);
$stmt->close();
$conn->close();
?>

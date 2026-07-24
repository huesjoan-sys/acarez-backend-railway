<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

$chofer = isset($_GET['chofer']) ? trim($_GET['chofer']) : '';
$fecha = isset($_GET['fecha']) ? trim($_GET['fecha']) : date('Y-m-d');

if ($chofer == '') {
    echo json_encode(['total' => 0]);
    exit;
}

// Contar viajes del chofer en la fecha especificada
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM viajes WHERE chofer = ? AND DATE(fecha) = ?");
$stmt->bind_param("ss", $chofer, $fecha);
$stmt->execute();
$result = $stmt->get_result();
$total = $result->fetch_assoc()['total'];

echo json_encode(['total' => $total]);

$stmt->close();
$conn->close();
?>

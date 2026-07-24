<?php
header('Content-Type: application/json');

$conn = new mysqli('localhost', 'root', '', 'acarez_logistica');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexión fallida']);
    exit;
}

$semana = $_GET['semana'] ?? '';
if (empty($semana)) {
    echo json_encode(['error' => 'No se especificó la semana']);
    exit;
}

$year = substr($semana, 0, 4);
$week = substr($semana, 6);
$fecha_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
$fecha_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));

$sql = "SELECT * FROM viajes WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin' ORDER BY id";
$result = $conn->query($sql);

$viajes = [];
$subtotal_semanal = 0;
$iva_semanal = 0;

while($row = $result->fetch_assoc()) {
    $subtotal = $row['total_general'];
    $iva = $subtotal * 0.16;
    $subtotal_semanal += $subtotal;
    $iva_semanal += $iva;
    
    $viajes[] = [
        'id' => $row['id'],
        'fecha' => date('d/m/Y', strtotime($row['fecha'])),
        'chofer' => $row['chofer'],
        'placas' => $row['placas'],
        'destino_ida' => $row['destino_ida'],
        'subtotal' => number_format($subtotal, 2),
        'total_con_iva' => number_format($subtotal + $iva, 2)
    ];
}

echo json_encode([
    'semana' => 'Semana ' . $week . ' del ' . $year,
    'fecha_inicio' => $fecha_inicio,
    'fecha_fin' => $fecha_fin,
    'total_viajes' => count($viajes),
    'subtotal_semanal' => number_format($subtotal_semanal, 2),
    'iva_semanal' => number_format($iva_semanal, 2),
    'total_semanal' => number_format($subtotal_semanal + $iva_semanal, 2),
    'viajes' => $viajes
]);

$conn->close();
?>

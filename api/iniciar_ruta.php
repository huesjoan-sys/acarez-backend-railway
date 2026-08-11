<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$chofer = trim($_POST['chofer'] ?? '');
$placas = trim($_POST['placas'] ?? '');
$no_economico = trim($_POST['no_economico'] ?? '');
$origen = trim($_POST['origen'] ?? '');
$km_inicial = floatval($_POST['km_inicial'] ?? 0);
$foto_inicio = trim($_POST['foto_inicio'] ?? '');

if (empty($chofer) || empty($placas) || empty($origen) || $km_inicial <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Faltan datos obligatorios']);
    exit;
}

$fecha_inicio = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO rutas (chofer, placas, no_economico, origen, fecha_inicio, km_inicial, foto_inicio, estatus) VALUES (?, ?, ?, ?, ?, ?, ?, 'activa')");
$stmt->bind_param("sssssds", $chofer, $placas, $no_economico, $origen, $fecha_inicio, $km_inicial, $foto_inicio);

if ($stmt->execute()) {
    $ruta_id = $conn->insert_id;
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Ruta iniciada correctamente',
        'ruta_id' => $ruta_id,
        'fecha_inicio' => $fecha_inicio
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

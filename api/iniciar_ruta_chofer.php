<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_POST['ruta_id'] ?? 0);
$km_inicial = floatval($_POST['km_inicial'] ?? 0);
$foto_inicio = trim($_POST['foto_inicio'] ?? '');
$placas = trim($_POST['placas'] ?? '');
$no_economico = trim($_POST['no_economico'] ?? '');
$origen_real = trim($_POST['origen_real'] ?? '');

if ($ruta_id <= 0 || $km_inicial <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Faltan datos obligatorios']);
    exit;
}

// Verificar que la ruta existe y está programada
$check = $conn->query("SELECT id FROM rutas WHERE id = $ruta_id AND estatus = 'programada'");
if ($check->num_rows == 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no encontrada o ya iniciada']);
    exit;
}

// Generar la fecha y hora exacta del momento en que el chofer inicia
$fecha_inicio = date('Y-m-d H:i:s');

// Actualizar la ruta incluyendo la hora real de inicio junto con los demás datos
if (!empty($placas) && !empty($no_economico) && !empty($origen_real)) {
    $stmt = $conn->prepare("UPDATE rutas SET placas = ?, no_economico = ?, km_inicial = ?, foto_inicio = ?, origen = ?, estatus = 'activa', fecha_inicio = ? WHERE id = ?");
    $stmt->bind_param("ssdssisi", $placas, $no_economico, $km_inicial, $foto_inicio, $origen_real, $fecha_inicio, $ruta_id);
} elseif (!empty($origen_real)) {
    $stmt = $conn->prepare("UPDATE rutas SET km_inicial = ?, foto_inicio = ?, origen = ?, estatus = 'activa', fecha_inicio = ? WHERE id = ?");
    $stmt->bind_param("dssisi", $km_inicial, $foto_inicio, $origen_real, $fecha_inicio, $ruta_id);
} elseif (!empty($placas) && !empty($no_economico)) {
    $stmt = $conn->prepare("UPDATE rutas SET placas = ?, no_economico = ?, km_inicial = ?, foto_inicio = ?, estatus = 'activa', fecha_inicio = ? WHERE id = ?");
    $stmt->bind_param("ssdsisi", $placas, $no_economico, $km_inicial, $foto_inicio, $fecha_inicio, $ruta_id);
} else {
    $stmt = $conn->prepare("UPDATE rutas SET km_inicial = ?, foto_inicio = ?, estatus = 'activa', fecha_inicio = ? WHERE id = ?");
    $stmt->bind_param("dsisi", $km_inicial, $foto_inicio, $fecha_inicio, $ruta_id);
}

if ($stmt->execute()) {
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

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_POST['ruta_id'] ?? 0);
$destino_final = trim($_POST['destino_final'] ?? '');
$km_final = floatval($_POST['km_final'] ?? 0);
$foto_fin = trim($_POST['foto_fin'] ?? '');

if ($ruta_id <= 0 || empty($destino_final) || $km_final <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Faltan datos obligatorios']);
    exit;
}

// Verificar que la ruta existe y está activa
$check = $conn->query("SELECT id, km_inicial FROM rutas WHERE id = $ruta_id AND estatus = 'activa'");
if ($check->num_rows == 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no encontrada o ya finalizada']);
    exit;
}
$ruta = $check->fetch_assoc();
$km_inicial = $ruta['km_inicial'];

// Calcular km total
$km_total = $km_final - $km_inicial;
if ($km_total < 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ El km final debe ser mayor al km inicial']);
    exit;
}

$fecha_fin = date('Y-m-d H:i:s');

$stmt = $conn->prepare("UPDATE rutas SET destino_final = ?, fecha_fin = ?, km_final = ?, km_total = ?, foto_fin = ?, estatus = 'completada' WHERE id = ?");
$stmt->bind_param("ssdssi", $destino_final, $fecha_fin, $km_final, $km_total, $foto_fin, $ruta_id);

if ($stmt->execute()) {
    // Obtener resumen de la ruta
    $resumen = $conn->query("SELECT total_gastos, numero_paradas FROM rutas WHERE id = $ruta_id")->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Ruta finalizada correctamente',
        'km_total' => $km_total,
        'total_gastos' => $resumen['total_gastos'],
        'numero_paradas' => $resumen['numero_paradas']
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

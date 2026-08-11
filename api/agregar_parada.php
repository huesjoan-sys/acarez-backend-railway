<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_POST['ruta_id'] ?? 0);
$orden = intval($_POST['orden'] ?? 0);
$destino_id = isset($_POST['destino_id']) && $_POST['destino_id'] != '' ? intval($_POST['destino_id']) : null;
$destino_manual = trim($_POST['destino_manual'] ?? '');
$km_actual = isset($_POST['km_actual']) ? floatval($_POST['km_actual']) : null;

// Gastos
$gasto_hotel = floatval($_POST['gasto_hotel'] ?? 0);
$gasto_caseta = floatval($_POST['gasto_caseta'] ?? 0);
$gasto_comida = floatval($_POST['gasto_comida'] ?? 0);
$gasto_estacionamiento = floatval($_POST['gasto_estacionamiento'] ?? 0);
$gasto_gasolina = floatval($_POST['gasto_gasolina'] ?? 0);

// Métodos de pago
$metodo_pago_hotel = trim($_POST['metodo_pago_hotel'] ?? 'Efectivo');
$metodo_pago_caseta = trim($_POST['metodo_pago_caseta'] ?? 'Efectivo');
$metodo_pago_comida = trim($_POST['metodo_pago_comida'] ?? 'Efectivo');
$metodo_pago_estacionamiento = trim($_POST['metodo_pago_estacionamiento'] ?? 'Efectivo');
$metodo_pago_gasolina = trim($_POST['metodo_pago_gasolina'] ?? 'Efectivo');

// Fotos de gastos
$foto_hotel = trim($_POST['foto_hotel'] ?? '');
$foto_caseta = trim($_POST['foto_caseta'] ?? '');
$foto_comida = trim($_POST['foto_comida'] ?? '');
$foto_estacionamiento = trim($_POST['foto_estacionamiento'] ?? '');
$foto_gasolina = trim($_POST['foto_gasolina'] ?? '');

if ($ruta_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no válida']);
    exit;
}

if (empty($destino_manual) && $destino_id === null) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Debes especificar un destino']);
    exit;
}

$stmt = $conn->prepare("INSERT INTO paradas (ruta_id, orden, destino_id, destino_manual, km_actual, gasto_hotel, gasto_caseta, gasto_comida, gasto_estacionamiento, gasto_gasolina, metodo_pago_hotel, metodo_pago_caseta, metodo_pago_comida, metodo_pago_estacionamiento, metodo_pago_gasolina, foto_hotel, foto_caseta, foto_comida, foto_estacionamiento, foto_gasolina) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iiisdssssssssssssss", $ruta_id, $orden, $destino_id, $destino_manual, $km_actual, $gasto_hotel, $gasto_caseta, $gasto_comida, $gasto_estacionamiento, $gasto_gasolina, $metodo_pago_hotel, $metodo_pago_caseta, $metodo_pago_comida, $metodo_pago_estacionamiento, $metodo_pago_gasolina, $foto_hotel, $foto_caseta, $foto_comida, $foto_estacionamiento, $foto_gasolina);

if ($stmt->execute()) {
    // Actualizar número de paradas en la ruta
    $conn->query("UPDATE rutas SET numero_paradas = numero_paradas + 1 WHERE id = $ruta_id");
    // Actualizar total de gastos en la ruta
    $total_gastos = $gasto_hotel + $gasto_caseta + $gasto_comida + $gasto_estacionamiento + $gasto_gasolina;
    $conn->query("UPDATE rutas SET total_gastos = total_gastos + $total_gastos WHERE id = $ruta_id");
    
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Parada agregada correctamente',
        'parada_id' => $conn->insert_id
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

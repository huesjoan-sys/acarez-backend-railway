<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$parada_id = intval($_POST['parada_id'] ?? 0);
$gasto_hotel = floatval($_POST['gasto_hotel'] ?? 0);
$gasto_caseta = floatval($_POST['gasto_caseta'] ?? 0);
$gasto_comida = floatval($_POST['gasto_comida'] ?? 0);
$gasto_estacionamiento = floatval($_POST['gasto_estacionamiento'] ?? 0);
$gasto_gasolina = floatval($_POST['gasto_gasolina'] ?? 0);

$metodo_pago_hotel = trim($_POST['metodo_pago_hotel'] ?? 'Efectivo');
$metodo_pago_caseta = trim($_POST['metodo_pago_caseta'] ?? 'Efectivo');
$metodo_pago_comida = trim($_POST['metodo_pago_comida'] ?? 'Efectivo');
$metodo_pago_estacionamiento = trim($_POST['metodo_pago_estacionamiento'] ?? 'Efectivo');
$metodo_pago_gasolina = trim($_POST['metodo_pago_gasolina'] ?? 'Efectivo');

$foto_hotel = trim($_POST['foto_hotel'] ?? '');
$foto_caseta = trim($_POST['foto_caseta'] ?? '');
$foto_comida = trim($_POST['foto_comida'] ?? '');
$foto_estacionamiento = trim($_POST['foto_estacionamiento'] ?? '');
$foto_gasolina = trim($_POST['foto_gasolina'] ?? '');

if ($parada_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Parada no válida']);
    exit;
}

$stmt = $conn->prepare("UPDATE paradas SET 
    gasto_hotel = ?, 
    gasto_caseta = ?, 
    gasto_comida = ?, 
    gasto_estacionamiento = ?, 
    gasto_gasolina = ?,
    metodo_pago_hotel = ?,
    metodo_pago_caseta = ?,
    metodo_pago_comida = ?,
    metodo_pago_estacionamiento = ?,
    metodo_pago_gasolina = ?,
    foto_hotel = ?,
    foto_caseta = ?,
    foto_comida = ?,
    foto_estacionamiento = ?,
    foto_gasolina = ?,
    completada = 1
WHERE id = ?");
$stmt->bind_param("dddddssssssssssi", 
    $gasto_hotel, $gasto_caseta, $gasto_comida, $gasto_estacionamiento, $gasto_gasolina,
    $metodo_pago_hotel, $metodo_pago_caseta, $metodo_pago_comida, $metodo_pago_estacionamiento, $metodo_pago_gasolina,
    $foto_hotel, $foto_caseta, $foto_comida, $foto_estacionamiento, $foto_gasolina,
    $parada_id
);

if ($stmt->execute()) {
    // Obtener ruta_id para actualizar total_gastos
    $ruta = $conn->query("SELECT ruta_id FROM paradas WHERE id = $parada_id")->fetch_assoc();
    $ruta_id = $ruta['ruta_id'];
    
    // Recalcular total_gastos de la ruta
    $total_gastos = $gasto_hotel + $gasto_caseta + $gasto_comida + $gasto_estacionamiento + $gasto_gasolina;
    $conn->query("UPDATE rutas SET total_gastos = total_gastos + $total_gastos WHERE id = $ruta_id");
    
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Parada completada correctamente'
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

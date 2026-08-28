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

// 1. Obtener la ruta_id vinculada a la parada
$resRuta = $conn->query("SELECT ruta_id FROM paradas WHERE id = $parada_id");
if (!$resRuta || $resRuta->num_rows === 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ No se encontró la ruta asociada']);
    exit;
}
$rutaData = $resRuta->fetch_assoc();
$ruta_id = intval($rutaData['ruta_id']);

// 2. Actualizar la tabla paradas como completada
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
    // 3. Insertar los gastos activos en la tabla 'gastos' para lectura de la APP
    $stmtGasto = $conn->prepare("INSERT INTO gastos (ruta_id, concepto, monto, foto, fecha) VALUES (?, ?, ?, ?, NOW())");

    $mapaGastos = [
        'Hotel / Hospedaje' => ['monto' => $gasto_hotel, 'foto' => $foto_hotel],
        'Caseta'            => ['monto' => $gasto_caseta, 'foto' => $foto_caseta],
        'Comida / Alimentos'=> ['monto' => $gasto_comida, 'foto' => $foto_comida],
        'Estacionamiento'   => ['monto' => $gasto_estacionamiento, 'foto' => $foto_estacionamiento],
        'Gasolina / Diesel' => ['monto' => $gasto_gasolina, 'foto' => $foto_gasolina],
    ];

    foreach ($mapaGastos as $concepto => $datos) {
        if ($datos['monto'] > 0) {
            $monto = $datos['monto'];
            $foto = $datos['foto'];
            $stmtGasto->bind_param("isds", $ruta_id, $concepto, $monto, $foto);
            $stmtGasto->execute();
        }
    }
    $stmtGasto->close();

    // 4. Recalcular total_gastos en la ruta
    $total_gastos = $gasto_hotel + $gasto_caseta + $gasto_comida + $gasto_estacionamiento + $gasto_gasolina;
    $conn->query("UPDATE rutas SET total_gastos = total_gastos + $total_gastos WHERE id = $ruta_id");
    
    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Parada y gastos registrados correctamente'
    ]);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>

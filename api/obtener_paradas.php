<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_GET['ruta_id'] ?? 0);

if ($ruta_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no válida']);
    exit;
}

// =======================================================
// 1. OBTENER LAS PARADAS DE LA RUTA
// =======================================================
$sql_paradas = "SELECT 
            p.id,
            p.ruta_id,
            p.destino_id,
            p.orden,
            p.km_actual,
            COALESCE(p.estatus, 'programada') AS estatus,
            d.razon_social, 
            d.sucursal,
            d.direccion
        FROM paradas p 
        LEFT JOIN destinos d ON p.destino_id = d.id 
        WHERE p.ruta_id = ? 
        ORDER BY p.orden ASC";

$stmt_paradas = $conn->prepare($sql_paradas);
$stmt_paradas->bind_param("i", $ruta_id);
$stmt_paradas->execute();
$result_paradas = $stmt_paradas->get_result();

$paradas = [];
while ($row = $result_paradas->fetch_assoc()) {
    $estatusLwr = strtolower(trim($row['estatus']));
    $esCompletado = ($estatusLwr === 'completado' || $estatusLwr === 'completada' || $estatusLwr === '1' || $row['km_actual'] !== null);
    
    $row['estatus'] = $esCompletado ? 'completado' : 'programada';
    $row['completado'] = $esCompletado;
    
    $paradas[] = $row;
}
$stmt_paradas->close();

// =======================================================
// 2. OBTENER LOS GASTOS ASOCIADOS A LA RUTA
// =======================================================
$sql_gastos = "SELECT 
            id,
            concepto,
            monto,
            foto,
            fecha
        FROM gastos 
        WHERE ruta_id = ? 
        ORDER BY id DESC";

$stmt_gastos = $conn->prepare($sql_gastos);
$stmt_gastos->bind_param("i", $ruta_id);
$stmt_gastos->execute();
$result_gastos = $stmt_gastos->get_result();

$gastos = [];
$total_gastos = 0.0;

while ($row_gasto = $result_gastos->fetch_assoc()) {
    $monto = floatval($row_gasto['monto']);
    $total_gastos += $monto;

    $gastos[] = [
        'id'       => intval($row_gasto['id']),
        'concepto' => $row_gasto['concepto'],
        'monto'    => $monto,
        'foto'     => $row_gasto['foto'] ?? '',
        'fecha'    => $row_gasto['fecha']
    ];
}
$stmt_gastos->close();

// =======================================================
// 3. RESPUESTA UNIFICADA EN JSON
// =======================================================
echo json_encode([
    'success'      => true,
    'paradas'      => $paradas,
    'gastos'       => $gastos,
    'total_gastos' => $total_gastos
]);

$conn->close();
?>

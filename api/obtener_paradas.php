<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once '../conexion.php';

$ruta_id = intval($_GET['ruta_id'] ?? 0);

if ($ruta_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Ruta no válida']);
    exit;
}

// Se elimina p.estado para evitar el fallo en MySQL
$sql = "SELECT 
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

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ruta_id);
$stmt->execute();
$result = $stmt->get_result();

$paradas = [];
while ($row = $result->fetch_assoc()) {
    $estatusLwr = strtolower(trim($row['estatus']));
    $esCompletado = ($estatusLwr === 'completado' || $estatusLwr === 'completada' || $estatusLwr === '1' || $row['km_actual'] !== null);
    
    $row['estatus'] = $esCompletado ? 'completado' : 'programada';
    $row['completado'] = $esCompletado;
    
    $paradas[] = $row;
}

echo json_encode(['success' => true, 'paradas' => $paradas]);

$stmt->close();
$conn->close();
?>

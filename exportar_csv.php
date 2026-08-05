<?php
ob_start();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reportes_acarez.csv"');
header('Cache-Control: max-age=0');

require_once 'conexion.php';

// Obtener filtros
$semana = $_GET['semana'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$todo = isset($_GET['todo']);

$where = "";
if (!$todo) {
    if (!empty($semana)) {
        $year = substr($semana, 0, 4);
        $week = substr($semana, 6);
        $fecha_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
        $fecha_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
        $where = "WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
        $where = "WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    }
}

$sql = "SELECT * FROM viajes $where ORDER BY id DESC";
$result = $conn->query($sql);

if (!$result) {
    echo "Error en la consulta: " . $conn->error;
    exit;
}

// Abrir la salida
$output = fopen('php://output', 'w');

// Encabezados CSV (con todas las columnas)
fputcsv($output, [
    'ID', 'FECHA', 'HORA', 'CHOFER', 'PLACAS', 'NO. ECONÓMICO',
    'ORIGEN IDA', 'DESTINO IDA', 'ORIGEN REGRESO', 'DESTINO REGRESO',
    'KM INICIAL', 'KM FINAL', 'KM TOTAL',
    'TOTAL IDA', 'TOTAL REGRESO', 'TOTAL GENERAL',
    'HOTEL IDA', 'HOTEL REG', 'CASETA IDA', 'CASETA REG',
    'COMIDA IDA', 'COMIDA REG', 'ESTAC. IDA', 'ESTAC. REG'
]);

while ($row = $result->fetch_assoc()) {
    $fecha = date('d/m/Y', strtotime($row['fecha']));
    $hora = date('H:i:s', strtotime($row['fecha']));
    
    // Limpiar caracteres especiales para CSV
    $chofer = str_replace(["\t", "\n", "\r", ","], " ", $row['chofer']);
    $placas = str_replace(["\t", "\n", "\r", ","], " ", $row['placas']);
    $no_economico = str_replace(["\t", "\n", "\r", ","], " ", $row['no_economico']);
    $origen_ida = str_replace(["\t", "\n", "\r", ","], " ", $row['origen_ida']);
    $destino_ida = str_replace(["\t", "\n", "\r", ","], " ", $row['destino_ida']);
    $origen_regreso = str_replace(["\t", "\n", "\r", ","], " ", $row['origen_regreso']);
    $destino_regreso = str_replace(["\t", "\n", "\r", ","], " ", $row['destino_regreso']);
    $direccion = str_replace(["\t", "\n", "\r", ","], " ", $row['direccion_actual']);
    
    fputcsv($output, [
        $row['id'],
        $fecha,
        $hora,
        $chofer,
        $placas,
        $no_economico,
        $origen_ida,
        $destino_ida,
        $origen_regreso,
        $destino_regreso,
        $row['km_inicial'] ?? 0,
        $row['km_final'] ?? 0,
        $row['km_total'] ?? 0,
        $row['total_ida'],
        $row['total_regreso'],
        $row['total_general'],
        $row['gasto_hotel_ida'],
        $row['gasto_hotel_reg'],
        $row['gasto_caseta_ida'],
        $row['gasto_caseta_reg'],
        $row['gasto_comida_ida'],
        $row['gasto_comida_reg'],
        $row['gasto_estac_ida'],
        $row['gasto_estac_reg']
    ]);
}

$conn->close();
fclose($output);
exit;
?>

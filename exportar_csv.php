<?php
ob_start();

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="reporte_rutas_acarez_' . date('Ymd_His') . '.csv"');
header('Cache-Control: max-age=0');

// BOM para Excel en español
echo "\xEF\xBB\xBF"; 

require_once 'conexion.php';

$semana = $_GET['semana'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$choferFiltro = $_GET['chofer'] ?? '';

$where = "1=1";
if (!empty($semana)) {
    $year = substr($semana, 0, 4);
    $week = substr($semana, 6);
    $f_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
    $f_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
    $where .= " AND DATE(r.fecha_inicio) BETWEEN '$f_inicio' AND '$f_fin'";
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where .= " AND DATE(r.fecha_inicio) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($choferFiltro)) {
    $choferEsc = $conn->real_escape_string($choferFiltro);
    $where .= " AND r.chofer = '$choferEsc'";
}

$sql = "SELECT r.*, 
               (SELECT COALESCE(SUM(g.monto), 0) FROM gastos g WHERE g.ruta_id = r.id) AS total_general
        FROM rutas r 
        WHERE $where 
        ORDER BY r.fecha_inicio DESC";

$result = $conn->query($sql);

if (!$result) {
    echo "Error en la consulta: " . $conn->error;
    exit;
}

$output = fopen('php://output', 'w');

fputcsv($output, [
    'ID RUTA', 'FECHA INICIO', 'HORA INICIO (KM INI)', 'FECHA FIN', 'HORA FIN (KM FIN)', 'CHOFER', 'AUXILIAR', 'PLACAS', 'NO. ECONOMICO',
    'ORIGEN', 'KM INICIAL', 'KM FINAL', 'KM RECORRIDO', 
    'DETALLE DE GASTOS', 'TOTAL GASTOS', 'ESTATUS'
]);

while ($row = $result->fetch_assoc()) {
    $f_inicio_formato = !empty($row['fecha_inicio']) ? date('d/m/Y', strtotime($row['fecha_inicio'])) : 'N/A';
    $h_inicio_formato = !empty($row['fecha_inicio']) ? date('H:i:s', strtotime($row['fecha_inicio'])) : 'N/A';
    
    $f_fin_formato = !empty($row['fecha_fin']) ? date('d/m/Y', strtotime($row['fecha_fin'])) : 'Pendiente';
    $h_fin_formato = !empty($row['fecha_fin']) ? date('H:i:s', strtotime($row['fecha_fin'])) : 'Pendiente';
    
    $id_ruta = $row['id'];
    $sql_gastos = "SELECT concepto, SUM(monto) as total_concepto FROM gastos WHERE ruta_id = $id_ruta GROUP BY concepto";
    $res_gastos = $conn->query($sql_gastos);
    $detalle_gastos = [];
    while ($g = $res_gastos->fetch_assoc()) {
        $detalle_gastos[] = $g['concepto'] . ': $' . number_format($g['total_concepto'], 2);
    }
    $texto_gastos = empty($detalle_gastos) ? 'Sin gastos' : implode(" | ", $detalle_gastos);

    $chofer = str_replace(["\t", "\n", "\r", ","], " ", $row['chofer']);
    $auxiliar = str_replace(["\t", "\n", "\r", ","], " ", $row['auxiliar'] ?? 'Sin auxiliar');
    $placas = str_replace(["\t", "\n", "\r", ","], " ", $row['placas']);
    $no_eco = str_replace(["\t", "\n", "\r", ","], " ", $row['no_economico']);
    $origen = str_replace(["\t", "\n", "\r", ","], " ", $row['origen']);
    
    fputcsv($output, [
        $row['id'],
        $f_inicio_formato,
        $h_inicio_formato,
        $f_fin_formato,
        $h_fin_formato,
        $chofer,
        $auxiliar,
        $placas,
        $no_eco,
        $origen,
        $row['km_inicial'] ?? 0,
        $row['km_final'] ?? 0,
        $row['km_total'] ?? 0,
        $texto_gastos,
        $row['total_general'] ?? 0,
        ucfirst($row['estatus'])
    ]);
}

$conn->close();
fclose($output);
exit;
?>

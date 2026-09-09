<?php
// ============================================
// exportar_excel.php - Reporte Excel Estilizado
// ============================================

session_start();
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reportes_acarez_' . date('Ymd_His') . '.xls"');
header('Cache-Control: max-age=0');

require_once 'conexion.php';

// Obtener filtros con sincronización idéntica al panel principal
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
    $where .= " AND DATE(fecha) BETWEEN '$f_inicio' AND '$f_fin'";
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where .= " AND DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}
if (!empty($choferFiltro)) {
    $choferEsc = $conn->real_escape_string($choferFiltro);
    $where .= " AND chofer = '$choferEsc'";
}

$sql = "SELECT * FROM viajes WHERE $where ORDER BY id DESC";
$result = $conn->query($sql);

if (!$result) {
    echo "Error en la consulta: " . $conn->error;
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte ACAREZ</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; }
        h1 { color: #4A148C; font-size: 18px; }
        .fecha { font-size: 12px; color: #666; margin-bottom: 15px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4A148C; color: #FFFFFF; font-weight: bold; padding: 8px 6px; border: 1px solid #3C096C; text-align: center; }
        td { padding: 6px 4px; border: 1px solid #ddd; text-align: left; }
        .numero { text-align: right; }
        .moneda { text-align: right; font-weight: 500; }
        .fila-alternativa { background-color: #f9f9f9; }
    </style>
</head>
<body>
    <h1>📋 Reporte de Viajes - ACAREZ LOGÍSTICA</h1>
    <div class="fecha">Generado: <?= date('d/m/Y H:i:s') ?></div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>FECHA</th>
                <th>HORA</th>
                <th>CHOFER</th>
                <th>PLACAS</th>
                <th>ORIGEN IDA</th>
                <th>DESTINO IDA</th>
                <th>ORIGEN REGRESO</th>
                <th>DESTINO REGRESO</th>
                <th>DIRECCIÓN</th>
                <th>KM INICIAL</th>
                <th>KM FINAL</th>
                <th>KM TOTAL</th>
                <th>TOTAL IDA</th>
                <th>TOTAL REGRESO</th>
                <th>TOTAL GENERAL</th>
                <th>HOTEL IDA</th>
                <th>HOTEL REG</th>
                <th>CASETA IDA</th>
                <th>CASETA REG</th>
                <th>COMIDA IDA</th>
                <th>COMIDA REG</th>
                <th>ESTAC. IDA</th>
                <th>ESTAC. REG</th>
            </tr>
        </thead>
        <tbody>
<?php
$cont = 0;
while ($row = $result->fetch_assoc()) {
    $cont++;
    $clase = ($cont % 2 == 0) ? 'fila-alternativa' : '';
    $fecha = date('d/m/Y', strtotime($row['fecha']));
    $hora = date('H:i:s', strtotime($row['fecha']));
    
    $chofer = htmlspecialchars($row['chofer'] ?? '');
    $placas = htmlspecialchars($row['placas'] ?? '');
    $origen_ida = htmlspecialchars($row['origen_ida'] ?? '');
    $destino_ida = htmlspecialchars($row['destino_ida'] ?? '');
    $origen_regreso = htmlspecialchars($row['origen_regreso'] ?? '');
    $destino_regreso = htmlspecialchars($row['destino_regreso'] ?? '');
    $direccion = htmlspecialchars($row['direccion_actual'] ?? '');
    
    $km_inicial = number_format($row['km_inicial'] ?? 0, 0, '.', '');
    $km_final   = number_format($row['km_final'] ?? 0, 0, '.', '');
    $km_total   = number_format($row['km_total'] ?? 0, 0, '.', '');
?>
            <tr class="<?= $clase ?>">
                <td style="text-align:center;"><?= $row['id'] ?></td>
                <td><?= $fecha ?></td>
                <td><?= $hora ?></td>
                <td><?= $chofer ?></td>
                <td><?= $placas ?></td>
                <td><?= $origen_ida ?></td>
                <td><?= $destino_ida ?></td>
                <td><?= $origen_regreso ?></td>
                <td><?= $destino_regreso ?></td>
                <td><?= $direccion ?></td>
                <td style="text-align:center;"><?= $km_inicial ?></td>
                <td style="text-align:center;"><?= $km_final ?></td>
                <td style="text-align:center; font-weight:bold;"><?= $km_total ?></td>
                <td style="text-align:right;">$<?= number_format($row['total_ida'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['total_regreso'] ?? 0, 2) ?></td>
                <td style="text-align:right; font-weight:bold; color:#4A148C;">$<?= number_format($row['total_general'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_hotel_ida'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_hotel_reg'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_caseta_ida'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_caseta_reg'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_comida_ida'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_comida_reg'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_estac_ida'] ?? 0, 2) ?></td>
                <td style="text-align:right;">$<?= number_format($row['gasto_estac_reg'] ?? 0, 2) ?></td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</body>
</html>
<?php
$conn->close();
exit;
?>

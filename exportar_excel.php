<?php
ob_start();

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reportes_acarez.xls"');
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
        .total-general { font-weight: bold; color: #4A148C; }
        .fila-alternativa { background-color: #f9f9f9; }
        .id-col { text-align: center; }
        .km-col { text-align: center; }
    </style>
</head>
<body>
    <h1>📋 Reporte de Viajes - ACAREZ</h1>
    <div class="fecha">Generado: <?= date('d/m/Y H:i:s') ?></div>
    <table>
        <thead>
            <tr>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">ID</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">FECHA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">HORA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">CHOFER</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">PLACAS</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">ORIGEN IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">DESTINO IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">ORIGEN REGRESO</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">DESTINO REGRESO</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C;">DIRECCIÓN</th>
                <!-- COLUMNAS DE KILOMETRAJE -->
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:center;">KM INICIAL</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:center;">KM FINAL</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:center;">KM TOTAL</th>
                <!-- FIN COLUMNAS KM -->
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">TOTAL IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">TOTAL REGRESO</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">TOTAL GENERAL</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">HOTEL IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">HOTEL REG</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">CASETA IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">CASETA REG</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">COMIDA IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">COMIDA REG</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">ESTAC. IDA</th>
                <th style="background-color:#4A148C; color:#FFFFFF; font-weight:bold; border:1px solid #3C096C; text-align:right;">ESTAC. REG</th>
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
    
    // Kilometraje (sin separadores de miles)
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
                <!-- KILOMETRAJE SIN COMAS -->
                <td style="text-align:center;"><?= $km_inicial ?></td>
                <td style="text-align:center;"><?= $km_final ?></td>
                <td style="text-align:center; font-weight:bold;"><?= $km_total ?></td>
                <!-- FIN KILOMETRAJE -->
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
ob_end_flush();
exit;
?>

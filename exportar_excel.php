<?php
// Iniciar buffer de salida para controlar cualquier salida no deseada
ob_start();

// Configurar cabeceras para Excel (HTML)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reportes_acarez.xls"');
header('Cache-Control: max-age=0');

// Incluir la conexión a la base de datos
require_once 'conexion.php';

// Obtener filtros
$semana = $_GET['semana'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';
$todo = isset($_GET['todo']);

// Construir consulta con filtros
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
    // Si hay error, mostrar mensaje simple y salir
    echo "Error en la consulta: " . $conn->error;
    exit;
}

// ========== GENERAR HTML CON FORMATO ==========
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte ACAREZ</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; font-size: 11px; }
        h1 { color: #4A148C; font-size: 18px; margin-bottom: 5px; }
        .fecha { font-size: 12px; color: #666; margin-bottom: 15px; }
        table { border-collapse: collapse; width: 100%; }
        th { background-color: #4A148C; color: white; font-weight: bold; padding: 8px 6px; border: 1px solid #3C096C; text-align: center; }
        td { padding: 6px 4px; border: 1px solid #ddd; text-align: left; }
        .numero { text-align: right; }
        .moneda { text-align: right; font-weight: 500; }
        .total-general { font-weight: bold; color: #4A148C; }
        .fila-alternativa { background-color: #f9f9f9; }
        .id-col { text-align: center; }
    </style>
</head>
<body>
    <h1>📋 Reporte de Viajes - ACAREZ</h1>
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
                <th class="numero">TOTAL IDA</th>
                <th class="numero">TOTAL REGRESO</th>
                <th class="numero">TOTAL GENERAL</th>
                <th class="numero">HOTEL IDA</th>
                <th class="numero">HOTEL REG</th>
                <th class="numero">CASETA IDA</th>
                <th class="numero">CASETA REG</th>
                <th class="numero">COMIDA IDA</th>
                <th class="numero">COMIDA REG</th>
                <th class="numero">ESTAC. IDA</th>
                <th class="numero">ESTAC. REG</th>
            </tr>
        </thead>
        <tbody>
<?php
$cont = 0;
while ($row = $result->fetch_assoc()) {
    $cont++;
    $clase = ($cont % 2 == 0) ? 'fila-alternativa' : '';
    $fecha_completa = $row['fecha'];
    $fecha = date('d/m/Y', strtotime($fecha_completa));
    $hora = date('H:i:s', strtotime($fecha_completa));
    
    $chofer = htmlspecialchars($row['chofer'] ?? '');
    $placas = htmlspecialchars($row['placas'] ?? '');
    $origen_ida = htmlspecialchars($row['origen_ida'] ?? '');
    $destino_ida = htmlspecialchars($row['destino_ida'] ?? '');
    $origen_regreso = htmlspecialchars($row['origen_regreso'] ?? '');
    $destino_regreso = htmlspecialchars($row['destino_regreso'] ?? '');
    $direccion = htmlspecialchars($row['direccion_actual'] ?? '');
?>
            <tr class="<?= $clase ?>">
                <td class="id-col"><?= $row['id'] ?></td>
                <td><?= $fecha ?></td>
                <td><?= $hora ?></td>
                <td><?= $chofer ?></td>
                <td><?= $placas ?></td>
                <td><?= $origen_ida ?></td>
                <td><?= $destino_ida ?></td>
                <td><?= $origen_regreso ?></td>
                <td><?= $destino_regreso ?></td>
                <td><?= $direccion ?></td>
                <td class="moneda">$<?= number_format($row['total_ida'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['total_regreso'] ?? 0, 2) ?></td>
                <td class="moneda total-general">$<?= number_format($row['total_general'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_hotel_ida'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_hotel_reg'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_caseta_ida'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_caseta_reg'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_comida_ida'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_comida_reg'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_estac_ida'] ?? 0, 2) ?></td>
                <td class="moneda">$<?= number_format($row['gasto_estac_reg'] ?? 0, 2) ?></td>
            </tr>
<?php } ?>
        </tbody>
    </table>
</body>
</html>
<?php
$conn->close();
// Vaciar buffer y enviar salida
ob_end_flush();
exit;
?>

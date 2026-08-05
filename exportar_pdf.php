<?php
// exportar_pdf.php
require_once 'vendor/autoload.php';
require_once 'conexion.php';

use Dompdf\Dompdf;

// Obtener filtros (igual que en exportar_excel.php)
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
    die("Error en la consulta: " . $conn->error);
}

// Inicializar variables para resúmenes
$total_viajes = 0;
$total_km = 0;
$total_gastos = 0;
$total_hotel = 0;
$total_caseta = 0;
$total_comida = 0;
$total_estac = 0;
$total_gasolina = 0;

// Construir el contenido HTML del PDF
$html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reporte de Viajes - ACAREZ</title>
    <style>
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 12px; margin: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { color: #4A148C; font-size: 22px; margin: 0; }
        .header .sub { color: #666; font-size: 14px; }
        .filtro { background: #f5f5f5; padding: 10px; margin-bottom: 20px; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; font-size: 10px; }
        th { background-color: #4A148C; color: white; padding: 6px 4px; border: 1px solid #3C096C; text-align: center; }
        td { padding: 5px 4px; border: 1px solid #ddd; text-align: left; }
        .numero { text-align: right; }
        .total { font-weight: bold; color: #4A148C; }
        .resumen { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .resumen table { width: auto; margin: 0 auto; border: none; }
        .resumen td { border: none; padding: 4px 15px; }
        .resumen .label { font-weight: bold; text-align: right; }
        .resumen .value { text-align: right; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #999; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>

<div class="header">
    <h1>🚚 ACAREZ LOGÍSTICA</h1>
    <div class="sub">Reporte de Viajes</div>
    <div class="sub">Generado: ' . date('d/m/Y H:i:s') . '</div>
</div>';

// Mostrar filtros aplicados
if (!empty($semana)) {
    $week = substr($semana, 6);
    $year = substr($semana, 0, 4);
    $html .= '<div class="filtro"><strong>Filtro:</strong> Semana ' . $week . ' del ' . $year . ' (' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . ')</div>';
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $html .= '<div class="filtro"><strong>Filtro:</strong> Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . '</div>';
} else {
    $html .= '<div class="filtro"><strong>Filtro:</strong> Todos los viajes</div>';
}

// Tabla de viajes
$html .= '<table>
<thead>
    <tr>
        <th>ID</th>
        <th>Fecha</th>
        <th>Hora</th>
        <th>Chofer</th>
        <th>Placas</th>
        <th>Origen Ida</th>
        <th>Destino Ida</th>
        <th>Origen Regreso</th>
        <th>Destino Regreso</th>
        <th>Km Inicial</th>
        <th>Km Final</th>
        <th>Km Recorrido</th>
        <th>Total Gastos</th>
    </tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $total_viajes++;
    $km_recorrido = $row['km_total'] ?? 0;
    $total_km += $km_recorrido;
    $total_gastos += $row['total_general'];
    $total_hotel += $row['gasto_hotel_ida'] + $row['gasto_hotel_reg'];
    $total_caseta += $row['gasto_caseta_ida'] + $row['gasto_caseta_reg'];
    $total_comida += $row['gasto_comida_ida'] + $row['gasto_comida_reg'];
    $total_estac += $row['gasto_estac_ida'] + $row['gasto_estac_reg'];
    $total_gasolina += $row['gasto_gasolina_ida'] + $row['gasto_gasolina_reg'];

    $fecha = date('d/m/Y', strtotime($row['fecha']));
    $hora = date('H:i:s', strtotime($row['fecha']));

    $html .= '<tr>
        <td style="text-align:center;">' . $row['id'] . '</td>
        <td>' . $fecha . '</td>
        <td>' . $hora . '</td>
        <td>' . htmlspecialchars($row['chofer']) . '</td>
        <td>' . htmlspecialchars($row['placas']) . '</td>
        <td>' . htmlspecialchars($row['origen_ida']) . '</td>
        <td>' . htmlspecialchars($row['destino_ida']) . '</td>
        <td>' . htmlspecialchars($row['origen_regreso']) . '</td>
        <td>' . htmlspecialchars($row['destino_regreso']) . '</td>
        <td style="text-align:center;">' . number_format($row['km_inicial'] ?? 0, 0, '.', '') . '</td>
        <td style="text-align:center;">' . number_format($row['km_final'] ?? 0, 0, '.', '') . '</td>
        <td style="text-align:center; font-weight:bold;">' . number_format($km_recorrido, 0, '.', '') . ' km</td>
        <td style="text-align:right; font-weight:bold; color:#4A148C;">$' . number_format($row['total_general'], 2) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// Resumen final
$html .= '
<div class="resumen">
    <h3 style="text-align:center; color:#4A148C;">📊 Resumen del Período</h3>
    <table>
        <tr><td class="label">Total de viajes:</td><td class="value">' . $total_viajes . '</td></tr>
        <tr><td class="label">Total Km recorridos:</td><td class="value">' . number_format($total_km, 0, '.', '') . ' km</td></tr>
        <tr><td class="label">Total gastos generales:</td><td class="value">$' . number_format($total_gastos, 2) . '</td></tr>
        <tr><td class="label">Gastos por categoría:</td><td></td></tr>
        <tr><td class="label" style="padding-left:20px;">Hotel:</td><td class="value">$' . number_format($total_hotel, 2) . '</td></tr>
        <tr><td class="label" style="padding-left:20px;">Caseta:</td><td class="value">$' . number_format($total_caseta, 2) . '</td></tr>
        <tr><td class="label" style="padding-left:20px;">Comida:</td><td class="value">$' . number_format($total_comida, 2) . '</td></tr>
        <tr><td class="label" style="padding-left:20px;">Estacionamiento:</td><td class="value">$' . number_format($total_estac, 2) . '</td></tr>
        <tr><td class="label" style="padding-left:20px; font-weight:bold; color:#4A148C;">Gasolina:</td><td class="value" style="font-weight:bold; color:#4A148C;">$' . number_format($total_gasolina, 2) . '</td></tr>
    </table>
</div>';

$html .= '
<div class="footer">
    Reporte generado automáticamente por ACAREZ. © ' . date('Y') . ' - Todos los derechos reservados.
</div>
</body>
</html>';

// ========== GENERAR PDF ==========
// Configurar opciones directamente en el objeto Dompdf (compatible con v0.6.2)
$dompdf = new Dompdf();
$dompdf->set_option('defaultFont', 'helvetica');
$dompdf->set_option('isRemoteEnabled', true); // para imágenes externas (logo)
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Salida al navegador (Attachment = 0 para mostrar en navegador, o 1 para descargar)
$dompdf->stream("reporte_viajes_" . date('Ymd_His') . ".pdf", array("Attachment" => 1));
exit;
?>

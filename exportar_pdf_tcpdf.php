<?php
// ============================================
// exportar_pdf_tcpdf.php - Reporte PDF con TCPDF
// ============================================

// Cargar TCPDF (ya está instalado en la carpeta tcpdf/)
require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/conexion.php';

// ========== OBTENER FILTROS ==========
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

// ========== VARIABLES PARA RESUMEN ==========
$total_viajes = 0;
$total_km = 0;
$total_gastos = 0;
$total_hotel = 0;
$total_caseta = 0;
$total_comida = 0;
$total_estac = 0;
$total_gasolina = 0;

// ========== CONSTRUIR HTML ==========
$html = '<h1 style="text-align:center; color:#4A148C;">🚚 ACAREZ LOGÍSTICA</h1>
<h2 style="text-align:center; font-size:14px;">Reporte de Viajes</h2>
<p style="text-align:center; font-size:12px; color:#666;">Generado: ' . date('d/m/Y H:i:s') . '</p>';

// Filtros aplicados
if (!empty($semana)) {
    $week = substr($semana, 6);
    $year = substr($semana, 0, 4);
    $html .= '<p><strong>Filtro:</strong> Semana ' . $week . ' del ' . $year . ' (' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . ')</p>';
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $html .= '<p><strong>Filtro:</strong> Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . '</p>';
} else {
    $html .= '<p><strong>Filtro:</strong> Todos los viajes</p>';
}

// Tabla de viajes
$html .= '<table border="1" cellpadding="4" style="font-size:10px; border-collapse:collapse; width:100%;">
<thead>
    <tr style="background-color:#4A148C; color:#FFFFFF; font-weight:bold;">
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

// Resumen del período
$html .= '<h3 style="text-align:center; color:#4A148C; margin-top:15px;">📊 Resumen del Período</h3>
<table border="0" cellpadding="5" style="margin:0 auto; font-size:12px;">
    <tr><td style="font-weight:bold;">Total de viajes:</td><td>' . $total_viajes . '</td></tr>
    <tr><td style="font-weight:bold;">Total Km recorridos:</td><td>' . number_format($total_km, 0, '.', '') . ' km</td></tr>
    <tr><td style="font-weight:bold;">Total gastos generales:</td><td>$' . number_format($total_gastos, 2) . '</td></tr>
    <tr><td style="font-weight:bold;">Gastos por categoría:</td><td></td></tr>
    <tr><td style="padding-left:20px;">Hotel:</td><td>$' . number_format($total_hotel, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Caseta:</td><td>$' . number_format($total_caseta, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Comida:</td><td>$' . number_format($total_comida, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Estacionamiento:</td><td>$' . number_format($total_estac, 2) . '</td></tr>
    <tr><td style="padding-left:20px; font-weight:bold; color:#4A148C;">Gasolina:</td><td style="font-weight:bold; color:#4A148C;">$' . number_format($total_gasolina, 2) . '</td></tr>
</table>';

// Pie de página
$html .= '<p style="text-align:center; font-size:10px; color:#999; margin-top:20px; border-top:1px solid #ddd; padding-top:10px;">
    Reporte generado automáticamente por ACAREZ. © ' . date('Y') . ' - Todos los derechos reservados.
</p>';

// ========== CREAR EL PDF CON TCPDF ==========
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('ACAREZ');
$pdf->SetAuthor('ACAREZ');
$pdf->SetTitle('Reporte de Viajes');
$pdf->SetSubject('Reporte de Viajes ACAREZ');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('reporte_viajes_' . date('Ymd_His') . '.pdf', 'D');
exit;
?>

<?php
// ============================================
// exportar_pdf_tcpdf.php - Reporte PDF (Landscape)
// ============================================

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
$html = '<h1 style="text-align:center; color:#4A148C; font-size:22px; margin-top:0; margin-bottom:2px;">Reporte de Viajes</h1>
<p style="text-align:center; font-size:11px; color:#666; margin-top:-5px;">Generado: ' . date('d/m/Y H:i:s') . '</p>';

// Filtros
if (!empty($semana)) {
    $week = substr($semana, 6);
    $year = substr($semana, 0, 4);
    $html .= '<p style="font-size:10px; text-align:center;"><strong>Filtro:</strong> Semana ' . $week . ' del ' . $year . ' (' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . ')</p>';
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $html .= '<p style="font-size:10px; text-align:center;"><strong>Filtro:</strong> Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . '</p>';
} else {
    $html .= '<p style="font-size:10px; text-align:center;"><strong>Filtro:</strong> Todos los viajes</p>';
}

// ========== TABLA (encabezados centrados) ==========
$html .= '<table border="1" cellpadding="3" style="font-size:8px; border-collapse:collapse; width:100%;">
<thead>
    <tr style="background-color:#4A148C; color:#FFFFFF; font-weight:bold;">
        <th style="text-align:center;">ID</th>
        <th style="text-align:center;">Fecha</th>
        <th style="text-align:center;">Hora</th>
        <th style="text-align:center;">Chofer</th>
        <th style="text-align:center;">Placas</th>
        <th style="text-align:center;">Origen Ida</th>
        <th style="text-align:center;">Destino Ida</th>
        <th style="text-align:center;">Origen Regreso</th>
        <th style="text-align:center;">Destino Regreso</th>
        <th style="text-align:center;">Km Ini</th>
        <th style="text-align:center;">Km Fin</th>
        <th style="text-align:center;">Km Total</th>
        <th style="text-align:center;">Total $</th>
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
        <td style="text-align:center;">' . $fecha . '</td>
        <td style="text-align:center;">' . $hora . '</td>
        <td>' . htmlspecialchars($row['chofer']) . '</td>
        <td>' . htmlspecialchars($row['placas']) . '</td>
        <td>' . htmlspecialchars($row['origen_ida']) . '</td>
        <td>' . htmlspecialchars($row['destino_ida']) . '</td>
        <td>' . htmlspecialchars($row['origen_regreso']) . '</td>
        <td>' . htmlspecialchars($row['destino_regreso']) . '</td>
        <td style="text-align:center;">' . number_format($row['km_inicial'] ?? 0, 0, '.', '') . '</td>
        <td style="text-align:center;">' . number_format($row['km_final'] ?? 0, 0, '.', '') . '</td>
        <td style="text-align:center; font-weight:bold;">' . number_format($km_recorrido, 0, '.', '') . '</td>
        <td style="text-align:right; font-weight:bold; color:#4A148C;">$' . number_format($row['total_general'], 2) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

// ========== RESUMEN ==========
$html .= '<h3 style="text-align:center; color:#4A148C; font-size:12px; margin-top:10px;">Resumen del Periodo</h3>
<table border="0" cellpadding="3" style="margin:0 auto; font-size:10px;">
    <tr><td style="font-weight:bold;">Total de viajes:</td><td>' . $total_viajes . '</td></tr>
    <tr><td style="font-weight:bold;">Total Km recorridos:</td><td>' . number_format($total_km, 0, '.', '') . ' km</td></tr>
    <tr><td style="font-weight:bold;">Total gastos generales:</td><td>$' . number_format($total_gastos, 2) . '</td></tr>
    <tr><td style="font-weight:bold;">Gastos por categoria:</td><td></td></tr>
    <tr><td style="padding-left:20px;">Hotel:</td><td>$' . number_format($total_hotel, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Caseta:</td><td>$' . number_format($total_caseta, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Comida:</td><td>$' . number_format($total_comida, 2) . '</td></tr>
    <tr><td style="padding-left:20px;">Estacionamiento:</td><td>$' . number_format($total_estac, 2) . '</td></tr>
    <tr><td style="padding-left:20px; font-weight:bold; color:#4A148C;">Gasolina:</td><td style="font-weight:bold; color:#4A148C;">$' . number_format($total_gasolina, 2) . '</td></tr>
</table>';

$html .= '<p style="text-align:center; font-size:9px; color:#999; margin-top:15px; border-top:1px solid #ddd; padding-top:8px;">
    Reporte generado automaticamente por ACAREZ. (c) ' . date('Y') . ' - Todos los derechos reservados.
</p>';

// ========== GENERAR PDF ==========

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(8, 8, 8);
$pdf->SetAutoPageBreak(true, 8);
$pdf->AddPage();

// ========== LOGO (más pequeño, con relación de aspecto) ==========
$logoPath = __DIR__ . '/imagenes/acarez_3.png';
if (file_exists($logoPath)) {
    // Alto 14 mm, ancho automático para mantener proporción (logo rectangular)
    $pdf->Image($logoPath, 8, 8, 0, 14, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// ========== CONTENIDO ==========
$pdf->SetY(25); // Dejar espacio para el logo (alto 14 mm + margen)
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($html, true, false, true, false, '');

// ========== SALIDA ==========
$pdf->Output('reporte_viajes_' . date('Ymd_His') . '.pdf', 'D');
exit;
?>

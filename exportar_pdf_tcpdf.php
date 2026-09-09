<?php
// ============================================
// exportar_pdf_tcpdf.php - Reporte PDF (Landscape) con Logo
// ============================================

date_default_timezone_set('America/Mexico_City');

require_once __DIR__ . '/tcpdf/tcpdf.php';
require_once __DIR__ . '/conexion.php';

// ========== OBTENER FILTROS SINCRONIZADOS ==========
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
               (SELECT COALESCE(SUM(g.monto), 0) FROM gastos g WHERE g.ruta_id = r.id) AS total_general,
               (SELECT COUNT(p.id) FROM paradas p WHERE p.ruta_id = r.id) AS total_paradas
        FROM rutas r 
        WHERE $where 
        ORDER BY r.fecha_inicio DESC";

$result = $conn->query($sql);
if (!$result) {
    die("Error en la consulta: " . $conn->error);
}

// ========== VARIABLES PARA RESUMEN ==========
$total_viajes = 0;
$total_km = 0;
$total_gastos = 0;

// ========== CONSTRUIR HTML ==========
$html = '<h1 style="text-align:center; color:#4A148C; font-size:20px; margin-top:0; margin-bottom:2px;">Reporte de Rutas y Gastos</h1>
<p style="text-align:center; font-size:10px; color:#666; margin-top:-3px;">Generado: ' . date('d/m/Y H:i:s') . '</p>';

if (!empty($semana)) {
    $week = substr($semana, 6);
    $year = substr($semana, 0, 4);
    $html .= '<p style="font-size:9px; text-align:center;"><strong>Filtro:</strong> Semana ' . $week . ' del ' . $year . '</p>';
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $html .= '<p style="font-size:9px; text-align:center;"><strong>Filtro:</strong> Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . '</p>';
} elseif (!empty($choferFiltro)) {
    $html .= '<p style="font-size:9px; text-align:center;"><strong>Filtro:</strong> Chofer: ' . htmlspecialchars($choferFiltro) . '</p>';
} else {
    $html .= '<p style="font-size:9px; text-align:center;"><strong>Filtro:</strong> Todos los registros</p>';
}

$html .= '<table border="1" cellpadding="4" style="font-size:9px; border-collapse:collapse; width:100%;">
<thead>
    <tr style="background-color:#4A148C; color:#FFFFFF; font-weight:bold;">
        <th style="text-align:center;" width="8%">ID Ruta</th>
        <th style="text-align:center;" width="16%">Fecha Inicio</th>
        <th style="text-align:center;" width="22%">Chofer</th>
        <th style="text-align:center;" width="15%">Placas / Eco</th>
        <th style="text-align:center;" width="12%">Km Total</th>
        <th style="text-align:center;" width="15%">Total Gastos</th>
        <th style="text-align:center;" width="12%">Estatus</th>
    </tr>
</thead>
<tbody>';

while ($row = $result->fetch_assoc()) {
    $total_viajes++;
    $km_recorrido = floatval($row['km_total'] ?? 0);
    $total_km += $km_recorrido;
    $total_gastos += floatval($row['total_general']);

    $fecha = date('d/m/Y H:i', strtotime($row['fecha_inicio']));

    $html .= '<tr>
        <td style="text-align:center;"><strong>#' . $row['id'] . '</strong></td>
        <td style="text-align:center;">' . $fecha . '</td>
        <td>' . htmlspecialchars($row['chofer']) . '</td>
        <td style="text-align:center;">' . htmlspecialchars($row['placas']) . ' (' . htmlspecialchars($row['no_economico']) . ')</td>
        <td style="text-align:center;">' . number_format($km_recorrido, 0) . ' km</td>
        <td style="text-align:right; font-weight:bold; color:#4A148C;">$' . number_format($row['total_general'], 2) . '</td>
        <td style="text-align:center;">' . ucfirst($row['estatus']) . '</td>
    </tr>';
}

$html .= '</tbody></table>';

$html .= '<h3 style="text-align:center; color:#4A148C; font-size:11px; margin-top:15px;">Resumen del Periodo</h3>
<table border="0" cellpadding="3" style="margin:0 auto; font-size:10px;">
    <tr><td style="font-weight:bold;">Total de rutas/viajes:</td><td>' . $total_viajes . '</td></tr>
    <tr><td style="font-weight:bold;">Total kilómetros recorridos:</td><td>' . number_format($total_km, 0) . ' km</td></tr>
    <tr><td style="font-weight:bold; color:#4A148C;">Gran total gastos comprobados:</td><td style="font-weight:bold; color:#4A148C;">$' . number_format($total_gastos, 2) . '</td></tr>
</table>';

$html .= '<p style="text-align:center; font-size:8px; color:#999; margin-top:15px; border-top:1px solid #ddd; padding-top:5px;">
    Reporte generado automáticamente por ACAREZ Logística. © ' . date('Y') . ' - Todos los derechos reservados.
</p>';

$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->AddPage();

$logoPath = __DIR__ . '/imagenes/acarez_3.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 10, 0, 12, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

$pdf->SetY(24);
$pdf->SetFont('helvetica', '', 9);
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('reporte_rutas_' . date('Ymd_His') . '.pdf', 'D');
exit;
?>

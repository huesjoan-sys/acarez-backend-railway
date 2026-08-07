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

// ========== CONSTRUIR HTML (CON FUENTES MÁS PEQUEÑAS) ==========
$html = '<h1 style="text-align:center; color:#4A148C; font-size:16px;">ACAREZ LOGISTICA</h1>
<h2 style="text-align:center; font-size:12px;">Reporte de Viajes</h2>
<p style="text-align:center; font-size:10px; color:#666;">Generado: ' . date('d/m/Y H:i:s') . '</p>';

// Filtros
if (!empty($semana)) {
    $week = substr($semana, 6);
    $year = substr($semana, 0, 4);
    $html .= '<p style="font-size:10px;"><strong>Filtro:</strong> Semana ' . $week . ' del ' . $year . ' (' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . ')</p>';
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $html .= '<p style="font-size:10px;"><strong>Filtro:</strong> Del ' . date('d/m/Y', strtotime($fecha_inicio)) . ' al ' . date('d/m/Y', strtotime($fecha_fin)) . '</p>';
} else {
    $html .= '<p style="font-size:10px;"><strong>Filtro:</strong> Todos los viajes</p>';
}

// Tabla con fuente más pequeña (8px) y padding reducido
$html .= '<table border="1" cellpadding="3" style="font-size:8px; border-collapse:collapse; width:100%;">
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

// Resumen (sin emojis)
$html .= '<h3 style="text-align:center; color:#4A148C; margin-top:10px; font-size:12px;">Resumen del Periodo</h3>
<table border="0" cellpadding="3" style="margin:0 auto; font-size:10px;">
    <tr><td style="font-weight:bold;">Total de viajes:</td><td>' . $total_viajes . '</td></tr>
    <tr><td style="font-weight:bold;">Total Km recorridos:</td><td>' . number_format($total_km, 0, '.', '') . ' km</td></tr>
    <tr><td style="font-weight:bold;">Total gastos generales:</td><td>$' . number_format($total_gastos, 2) . '</td></tr>
    <tr><td style="font-weight:bold;">Gastos por categoria:</td><td></td></tr>
    <tr><td style="padding-left:15px;">Hotel:</td><td>$' . number_format($total_hotel, 2) . '</td></tr>
    <tr><td style="padding-left:15px;">Caseta:</td><td>$' . number_format($total_caseta, 2) . '</td></tr>
    <tr><td style="padding-left:15px;">Comida:</td><td>$' . number_format($total_comida, 2) . '</td></tr>
    <tr><td style="padding-left:15px;">Estacionamiento:</td><td>$' . number_format($total_estac, 2) . '</td></tr>
    <tr><td style="padding-left:15px; font-weight:bold; color:#4A148C;">Gasolina:</td><td style="font-weight:bold; color:#4A148C;">$' . number_format($total_gasolina, 2) . '</td></tr>
</table>';

// Pie de página
$html .= '<p style="text-align:center; font-size:8px; color:#999; margin-top:15px; border-top:1px solid #ddd; padding-top:5px;">
    Reporte generado automaticamente por ACAREZ. (c) ' . date('Y') . ' - Todos los derechos reservados.
</p>';

// ========== CREAR PDF CON TCPDF (LANDSCAPE, LOGO, MÁRGENES REDUCIDOS) ==========

// Crear PDF en orientación Landscape (L) y tamaño A4
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Eliminar cabeceras y pies de página por defecto
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Configurar márgenes reducidos (superior 8, inferior 8, izquierdo 10, derecho 10)
$pdf->SetMargins(10, 8, 10);
$pdf->SetAutoPageBreak(true, 8);

// Agregar página
$pdf->AddPage();

// ========== AGREGAR LOGO MÁS GRANDE Y ENCABEZADO ==========
// Logo más grande (50x50)
$logoPath = __DIR__ . '/imagenes/acarez_3.png';
if (file_exists($logoPath)) {
    $pdf->Image($logoPath, 10, 5, 45, 45, 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Título principal al lado del logo (más grande)
$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetXY(60, 10);
$pdf->Cell(0, 12, 'ACAREZ LOGISTICA', 0, 1, 'L');

$pdf->SetFont('helvetica', '', 13);
$pdf->SetXY(60, 22);
$pdf->Cell(0, 10, 'Reporte de Viajes', 0, 1, 'L');

$pdf->SetFont('helvetica', 'I', 10);
$pdf->SetXY(60, 31);
$pdf->Cell(0, 10, 'Generado: ' . date('d/m/Y H:i:s'), 0, 1, 'L');

// ========== AGREGAR EL CONTENIDO HTML (TABLA Y RESUMEN) ==========
$pdf->SetY(42); // Ajustar posición después del encabezado
$pdf->SetFont('helvetica', '', 9); // Fuente base para el contenido HTML
$pdf->writeHTML($html, true, false, true, false, '');

// ========== SALIDA ==========
$pdf->Output('reporte_viajes_' . date('Ymd_His') . '.pdf', 'D');
exit;
?>

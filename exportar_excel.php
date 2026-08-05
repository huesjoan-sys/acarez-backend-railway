<?php
// Limpiar cualquier salida previa (espacios, BOM, etc.)
ob_clean();

// Configurar cabeceras para Excel
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="reportes_acarez.xls"');
header('Cache-Control: max-age=0');

// Usar la conexión centralizada (compatible con Render/Railway)
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
    die("Error en la consulta: " . $conn->error);
}

// Encabezados del Excel (con separadores TAB)
echo "ID\tFECHA\tHORA\tChofer\tPlacas\tOrigen Ida\tDestino Ida\tOrigen Regreso\tDestino Regreso\tDirección\t";
echo "Total Ida\tTotal Regreso\tTotal General\t";
echo "Hotel Ida\tHotel Regreso\tCaseta Ida\tCaseta Regreso\t";
echo "Comida Ida\tComida Regreso\tEstacionamiento Ida\tEstacionamiento Regreso\n";

while ($row = $result->fetch_assoc()) {
    // Separar fecha y hora
    $fecha_completa = $row['fecha'];
    $fecha = date('d/m/Y', strtotime($fecha_completa));
    $hora = date('H:i:s', strtotime($fecha_completa));
    
    // Escapar caracteres que puedan romper el formato (opcional)
    $chofer = str_replace(["\t", "\n", "\r"], " ", $row['chofer']);
    $placas = str_replace(["\t", "\n", "\r"], " ", $row['placas']);
    $origen_ida = str_replace(["\t", "\n", "\r"], " ", $row['origen_ida']);
    $destino_ida = str_replace(["\t", "\n", "\r"], " ", $row['destino_ida']);
    $origen_regreso = str_replace(["\t", "\n", "\r"], " ", $row['origen_regreso']);
    $destino_regreso = str_replace(["\t", "\n", "\r"], " ", $row['destino_regreso']);
    $direccion = str_replace(["\t", "\n", "\r"], " ", $row['direccion_actual']);

    echo $row['id'] . "\t";
    echo $fecha . "\t";
    echo $hora . "\t";
    echo $chofer . "\t";
    echo $placas . "\t";
    echo $origen_ida . "\t";
    echo $destino_ida . "\t";
    echo $origen_regreso . "\t";
    echo $destino_regreso . "\t";
    echo $direccion . "\t";
    echo $row['total_ida'] . "\t";
    echo $row['total_regreso'] . "\t";
    echo $row['total_general'] . "\t";
    echo $row['gasto_hotel_ida'] . "\t";
    echo $row['gasto_hotel_reg'] . "\t";
    echo $row['gasto_caseta_ida'] . "\t";
    echo $row['gasto_caseta_reg'] . "\t";
    echo $row['gasto_comida_ida'] . "\t";
    echo $row['gasto_comida_reg'] . "\t";
    echo $row['gasto_estac_ida'] . "\t";
    echo $row['gasto_estac_reg'] . "\n";
}

$conn->close();
exit;
?>

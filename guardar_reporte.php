<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

// ==================== RECIBIR TODOS LOS CAMPOS ====================
$chofer = $conn->real_escape_string($_POST['chofer'] ?? '');
$placas = $conn->real_escape_string($_POST['placas'] ?? '');
$no_economico = $conn->real_escape_string($_POST['no_economico'] ?? '');
$origen_ida = $conn->real_escape_string($_POST['origen_ida'] ?? '');
$destino_ida = $conn->real_escape_string($_POST['destino_ida'] ?? '');
$origen_regreso = $conn->real_escape_string($_POST['origen_regreso'] ?? '');
$destino_regreso = $conn->real_escape_string($_POST['destino_regreso'] ?? '');
$direccion_actual = $conn->real_escape_string($_POST['direccion_actual'] ?? '');

// ==================== KILOMETRAJE ====================
$km_inicial = floatval($_POST['km_inicial'] ?? 0);
$km_final   = floatval($_POST['km_final'] ?? 0);
$km_total   = floatval($_POST['km_total'] ?? 0);

$total_ida = floatval($_POST['total_ida'] ?? 0);
$total_regreso = floatval($_POST['total_regreso'] ?? 0);
$total_general = $total_ida + $total_regreso;
$foto_inicio = $conn->real_escape_string($_POST['foto_inicio'] ?? '');
$foto_fin = $conn->real_escape_string($_POST['foto_fin'] ?? '');
$gasto_hotel_ida = floatval($_POST['gasto_hotel_ida'] ?? 0);
$gasto_hotel_reg = floatval($_POST['gasto_hotel_reg'] ?? 0);
$gasto_caseta_ida = floatval($_POST['gasto_caseta_ida'] ?? 0);
$gasto_caseta_reg = floatval($_POST['gasto_caseta_reg'] ?? 0);
$gasto_comida_ida = floatval($_POST['gasto_comida_ida'] ?? 0);
$gasto_comida_reg = floatval($_POST['gasto_comida_reg'] ?? 0);
$gasto_estac_ida = floatval($_POST['gasto_estac_ida'] ?? 0);
$gasto_estac_reg = floatval($_POST['gasto_estac_reg'] ?? 0);
$foto_hotel_ida = $conn->real_escape_string($_POST['foto_hotel_ida'] ?? '');
$foto_hotel_regreso = $conn->real_escape_string($_POST['foto_hotel_regreso'] ?? '');
$foto_caseta_ida = $conn->real_escape_string($_POST['foto_caseta_ida'] ?? '');
$foto_caseta_regreso = $conn->real_escape_string($_POST['foto_caseta_regreso'] ?? '');
$foto_comida_ida = $conn->real_escape_string($_POST['foto_comida_ida'] ?? '');
$foto_comida_regreso = $conn->real_escape_string($_POST['foto_comida_regreso'] ?? '');
$foto_estac_ida = $conn->real_escape_string($_POST['foto_estac_ida'] ?? '');
$foto_estac_regreso = $conn->real_escape_string($_POST['foto_estac_regreso'] ?? '');

// ==================== CAMPOS DE GASOLINA ====================
$gasto_gasolina_ida = floatval($_POST['gasto_gasolina_ida'] ?? 0);
$gasto_gasolina_reg = floatval($_POST['gasto_gasolina_regreso'] ?? 0);
$foto_gasolina_ida = $conn->real_escape_string($_POST['foto_gasolina_ida'] ?? '');
$foto_gasolina_regreso = $conn->real_escape_string($_POST['foto_gasolina_regreso'] ?? '');
$metodo_pago_gasolina_ida = $conn->real_escape_string($_POST['metodo_pago_gasolina_ida'] ?? 'Efectivo');
$metodo_pago_gasolina_regreso = $conn->real_escape_string($_POST['metodo_pago_gasolina_regreso'] ?? 'Efectivo');

// Métodos de pago existentes
$metodo_pago_hotel_ida = $conn->real_escape_string($_POST['metodo_pago_hotel_ida'] ?? 'Efectivo');
$metodo_pago_hotel_regreso = $conn->real_escape_string($_POST['metodo_pago_hotel_regreso'] ?? 'Efectivo');
$metodo_pago_caseta_ida = $conn->real_escape_string($_POST['metodo_pago_caseta_ida'] ?? 'Efectivo');
$metodo_pago_caseta_regreso = $conn->real_escape_string($_POST['metodo_pago_caseta_regreso'] ?? 'Efectivo');
$metodo_pago_comida_ida = $conn->real_escape_string($_POST['metodo_pago_comida_ida'] ?? 'Efectivo');
$metodo_pago_comida_regreso = $conn->real_escape_string($_POST['metodo_pago_comida_regreso'] ?? 'Efectivo');
$metodo_pago_estac_ida = $conn->real_escape_string($_POST['metodo_pago_estac_ida'] ?? 'Efectivo');
$metodo_pago_estac_regreso = $conn->real_escape_string($_POST['metodo_pago_estac_regreso'] ?? 'Efectivo');

// ==================== CONSTRUIR INSERT (con km y gasolina) ====================
$sql = "INSERT INTO viajes (
    chofer, placas, no_economico, origen_ida, destino_ida,
    origen_regreso, destino_regreso, direccion_actual,
    km_inicial, km_final, km_total,
    total_ida, total_regreso, total_general,
    foto_inicio, foto_fin,
    gasto_hotel_ida, gasto_hotel_reg,
    gasto_caseta_ida, gasto_caseta_reg,
    gasto_comida_ida, gasto_comida_reg,
    gasto_estac_ida, gasto_estac_reg,
    gasto_gasolina_ida, gasto_gasolina_reg,
    foto_hotel_ida, foto_hotel_regreso,
    foto_caseta_ida, foto_caseta_regreso,
    foto_comida_ida, foto_comida_regreso,
    foto_estac_ida, foto_estac_regreso,
    foto_gasolina_ida, foto_gasolina_regreso,
    metodo_pago_hotel_ida, metodo_pago_hotel_regreso,
    metodo_pago_caseta_ida, metodo_pago_caseta_regreso,
    metodo_pago_comida_ida, metodo_pago_comida_regreso,
    metodo_pago_estac_ida, metodo_pago_estac_regreso,
    metodo_pago_gasolina_ida, metodo_pago_gasolina_regreso
) VALUES (
    '$chofer', '$placas', '$no_economico', '$origen_ida', '$destino_ida',
    '$origen_regreso', '$destino_regreso', '$direccion_actual',
    $km_inicial, $km_final, $km_total,
    $total_ida, $total_regreso, $total_general,
    '$foto_inicio', '$foto_fin',
    $gasto_hotel_ida, $gasto_hotel_reg,
    $gasto_caseta_ida, $gasto_caseta_reg,
    $gasto_comida_ida, $gasto_comida_reg,
    $gasto_estac_ida, $gasto_estac_reg,
    $gasto_gasolina_ida, $gasto_gasolina_reg,
    '$foto_hotel_ida', '$foto_hotel_regreso',
    '$foto_caseta_ida', '$foto_caseta_regreso',
    '$foto_comida_ida', '$foto_comida_regreso',
    '$foto_estac_ida', '$foto_estac_regreso',
    '$foto_gasolina_ida', '$foto_gasolina_regreso',
    '$metodo_pago_hotel_ida', '$metodo_pago_hotel_regreso',
    '$metodo_pago_caseta_ida', '$metodo_pago_caseta_regreso',
    '$metodo_pago_comida_ida', '$metodo_pago_comida_regreso',
    '$metodo_pago_estac_ida', '$metodo_pago_estac_regreso',
    '$metodo_pago_gasolina_ida', '$metodo_pago_gasolina_regreso'
)";

// ==================== EJECUTAR ====================
if ($conn->query($sql) === TRUE) {
    echo json_encode(['mensaje' => '✅ Reporte guardado correctamente']);
} else {
    echo json_encode(['mensaje' => '❌ Error SQL: ' . $conn->error]);
}
$conn->close();
?>

<?php
include "conexion.php"; // tu conexión a la base de datos

// === RECIBIR DATOS DESDE FLUTTER ===
$chofer = $_POST['chofer'] ?? '';
$placas = $_POST['placas'] ?? '';

$origen_ida = $_POST['origen_ida'] ?? '';
$destino_ida = $_POST['destino_ida'] ?? '';

$origen_regreso = $_POST['origen_regreso'] ?? '';
$destino_regreso = $_POST['destino_regreso'] ?? '';

$total_ida = $_POST['total_ida'] ?? 0;
$total_regreso = $_POST['total_regreso'] ?? 0;

$direccion = $_POST['direccion'] ?? '';

// === FOTOS ===
$foto_inicio = basename($_POST['foto_inicio'] ?? '');
$foto_fin    = basename($_POST['foto_fin'] ?? '');
$foto_ticket = basename($_POST['foto_ticket'] ?? '');

// === GASTOS (ida y regreso) ===
$gasto_hotel_ida    = $_POST['gasto_hotel_ida'] ?? 0;
$gasto_hotel_reg    = $_POST['gasto_hotel_reg'] ?? 0;
$gasto_caseta_ida   = $_POST['gasto_caseta_ida'] ?? 0;
$gasto_caseta_reg   = $_POST['gasto_caseta_reg'] ?? 0;
$gasto_comida_ida   = $_POST['gasto_comida_ida'] ?? 0;
$gasto_comida_reg   = $_POST['gasto_comida_reg'] ?? 0;
$gasto_estac_ida    = $_POST['gasto_estac_ida'] ?? 0;
$gasto_estac_reg    = $_POST['gasto_estac_reg'] ?? 0;

// === INSERTAR EN LA TABLA VIAJES ===
$sql = "INSERT INTO viajes (
    chofer, placas, origen_ida, destino_ida, origen_regreso, destino_regreso,
    total_ida, total_regreso, direccion,
    foto_inicio, foto_fin, foto_ticket,
    gasto_hotel_ida, gasto_hotel_reg,
    gasto_caseta_ida, gasto_caseta_reg,
    gasto_comida_ida, gasto_comida_reg,
    gasto_estac_ida, gasto_estac_reg
) VALUES (
    '$chofer', '$placas', '$origen_ida', '$destino_ida', '$origen_regreso', '$destino_regreso',
    '$total_ida', '$total_regreso', '$direccion',
    '$foto_inicio', '$foto_fin', '$foto_ticket',
    '$gasto_hotel_ida', '$gasto_hotel_reg',
    '$gasto_caseta_ida', '$gasto_caseta_reg',
    '$gasto_comida_ida', '$gasto_comida_reg',
    '$gasto_estac_ida', '$gasto_estac_reg'
)";

if ($conexion->query($sql)) {
    echo json_encode(["mensaje" => "Reporte guardado correctamente"]);
} else {
    echo json_encode(["mensaje" => "Error: " . $conexion->error]);
}

?>

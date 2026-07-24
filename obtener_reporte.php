<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli('localhost', 'root', '', 'acarez_logistica');

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexión fallida: ' . $conn->connect_error]);
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id == 0) {
    echo json_encode(['error' => 'ID no válido']);
    exit;
}

$sql = "SELECT * FROM viajes WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo json_encode(['error' => 'Viaje no encontrado']);
    exit;
}

$row = $result->fetch_assoc();

// Asegurar que todos los campos existen
$datos = [
    'id' => $row['id'],
    'chofer' => $row['chofer'] ?? '',
    'placas' => $row['placas'] ?? '',
    'no_economico' => $row['no_economico'] ?? '',
    'origen_ida' => $row['origen_ida'] ?? '',
    'destino_ida' => $row['destino_ida'] ?? '',
    'origen_regreso' => $row['origen_regreso'] ?? '',
    'destino_regreso' => $row['destino_regreso'] ?? '',
    'direccion_actual' => $row['direccion_actual'] ?? '',
    'total_ida' => $row['total_ida'] ?? 0,
    'total_regreso' => $row['total_regreso'] ?? 0,
    'total_general' => $row['total_general'] ?? 0,
    'fecha' => $row['fecha'] ?? '',
    'foto_inicio' => $row['foto_inicio'] ?? '',
    'foto_fin' => $row['foto_fin'] ?? '',
    // ========== KILOMETRAJE (NUEVOS CAMPOS) ==========
    'km_inicial' => $row['km_inicial'] ?? 0,
    'km_final' => $row['km_final'] ?? 0,
    'km_total' => $row['km_total'] ?? 0,
    // =================================================
    'gasto_hotel_ida' => $row['gasto_hotel_ida'] ?? 0,
    'gasto_hotel_reg' => $row['gasto_hotel_reg'] ?? 0,
    'gasto_caseta_ida' => $row['gasto_caseta_ida'] ?? 0,
    'gasto_caseta_reg' => $row['gasto_caseta_reg'] ?? 0,
    'gasto_comida_ida' => $row['gasto_comida_ida'] ?? 0,
    'gasto_comida_reg' => $row['gasto_comida_reg'] ?? 0,
    'gasto_estac_ida' => $row['gasto_estac_ida'] ?? 0,
    'gasto_estac_reg' => $row['gasto_estac_reg'] ?? 0,
    // ========== NUEVOS CAMPOS DE GASOLINA ==========
    'gasto_gasolina_ida' => $row['gasto_gasolina_ida'] ?? 0,
    'gasto_gasolina_reg' => $row['gasto_gasolina_reg'] ?? 0,
    'foto_gasolina_ida' => $row['foto_gasolina_ida'] ?? '',
    'foto_gasolina_regreso' => $row['foto_gasolina_regreso'] ?? '',
    // ================================================
    'foto_hotel_ida' => $row['foto_hotel_ida'] ?? '',
    'foto_hotel_regreso' => $row['foto_hotel_regreso'] ?? '',
    'foto_caseta_ida' => $row['foto_caseta_ida'] ?? '',
    'foto_caseta_regreso' => $row['foto_caseta_regreso'] ?? '',
    'foto_comida_ida' => $row['foto_comida_ida'] ?? '',
    'foto_comida_regreso' => $row['foto_comida_regreso'] ?? '',
    'foto_estac_ida' => $row['foto_estac_ida'] ?? '',
    'foto_estac_regreso' => $row['foto_estac_regreso'] ?? ''
];

echo json_encode($datos, JSON_UNESCAPED_UNICODE);
$conn->close();
?>

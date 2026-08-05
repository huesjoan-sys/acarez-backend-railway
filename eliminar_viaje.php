<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once 'conexion.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => 'ID inválido']);
    exit;
}

// Obtener las rutas de las fotos antes de eliminar
$sql = "SELECT foto_inicio, foto_fin, foto_hotel_ida, foto_hotel_regreso, 
               foto_caseta_ida, foto_caseta_regreso, foto_comida_ida, foto_comida_regreso,
               foto_estac_ida, foto_estac_regreso, foto_gasolina_ida, foto_gasolina_regreso
        FROM viajes WHERE id = $id";
$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'mensaje' => 'Viaje no encontrado']);
    exit;
}

$row = $result->fetch_assoc();

// Recorrer todas las columnas de fotos y eliminar los archivos
$fotos = array_filter($row); // elimina valores vacíos
foreach ($fotos as $ruta) {
    if (!empty($ruta) && file_exists($ruta)) {
        unlink($ruta); // eliminar archivo físico
    }
}

// Eliminar el registro de la base de datos
$delete = $conn->query("DELETE FROM viajes WHERE id = $id");

if ($delete) {
    echo json_encode(['success' => true, 'mensaje' => '✅ Viaje y fotos eliminados correctamente']);
} else {
    echo json_encode(['success' => false, 'mensaje' => '❌ Error al eliminar: ' . $conn->error]);
}

$conn->close();
?>

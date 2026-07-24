<?php
header('Content-Type: application/json');

$carpetas = [
    'inicio' => 'km_inicio/',
    'fin' => 'km_fin/',
    'hotel_ida' => 'hotel/',
    'hotel_regreso' => 'hotel/',
    'caseta_ida' => 'caseta/',
    'caseta_regreso' => 'caseta/',
    'comida_ida' => 'comida/',
    'comida_regreso' => 'comida/',
    'estac_ida' => 'estacionamiento/',
    'estac_regreso' => 'estacionamiento/',
    'gasolina_ida' => 'gasolina/',      // ← NUEVO
    'gasolina_regreso' => 'gasolina/',  // ← NUEVO
];

$tipo = $_POST['tipo'] ?? 'general';
$carpeta_base = "uploads/";
$subcarpeta = $carpetas[$tipo] ?? 'otros/';

$carpeta_destino = $carpeta_base . $subcarpeta;
if (!file_exists($carpeta_destino)) {
    mkdir($carpeta_destino, 0777, true);
}

if ($_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $extension = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $nombre_archivo = uniqid($tipo . '_') . '.' . $extension;
    $ruta_completa = $carpeta_destino . $nombre_archivo;
    
    if (move_uploaded_file($_FILES['foto']['tmp_name'], $ruta_completa)) {
        echo json_encode(['success' => true, 'ruta' => $ruta_completa]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No se pudo mover']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Error al subir: ' . $_FILES['foto']['error']]);
}
?>

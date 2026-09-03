<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../conexion.php';

// Activar excepciones estrictas
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Recibir IDs y datos generales
$parada_id = intval($_POST['parada_id'] ?? 0);
$km_actual = isset($_POST['km_actual']) && $_POST['km_actual'] !== '' ? floatval($_POST['km_actual']) : null;

if ($parada_id <= 0) {
    echo json_encode(['success' => false, 'mensaje' => '❌ Parada no válida']);
    exit;
}

// Configuración del directorio para guardar las fotos en el servidor
// Asegúrate de que esta ruta exista y tenga permisos de escritura
$upload_dir = '../uploads/gastos/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

try {
    // 1. Obtener la ruta_id vinculada a esta parada
    $stmtRuta = $conn->prepare("SELECT ruta_id FROM paradas WHERE id = ?");
    $stmtRuta->bind_param("i", $parada_id);
    $stmtRuta->execute();
    $resRuta = $stmtRuta->get_result();

    if ($resRuta->num_rows === 0) {
        echo json_encode(['success' => false, 'mensaje' => '❌ No se encontró la parada']);
        $stmtRuta->close();
        exit;
    }
    
    $rutaData = $resRuta->fetch_assoc();
    $ruta_id = intval($rutaData['ruta_id']);
    $stmtRuta->close();

    // Iniciar transacción SQL
    $conn->begin_transaction();

    // 2. Actualizar el estado de la parada y el kilometraje
    // Ya no intentamos guardar gastos aquí porque la tabla 'paradas' no tiene esas columnas
    $stmtParada = $conn->prepare("UPDATE paradas SET estatus = 'completada', completada = 1, km_actual = ? WHERE id = ?");
    $stmtParada->bind_param("di", $km_actual, $parada_id);
    $stmtParada->execute();
    $stmtParada->close();

    // 3. Procesar Gastos y Fotos
    // Mapeamos los conceptos con los nombres de las variables que llegarán desde Flutter
    $conceptos_keys = [
        'Hotel / Hospedaje' => 'hotel',
        'Caseta'            => 'caseta',
        'Comida / Alimentos'=> 'comida',
        'Estacionamiento'   => 'estacionamiento',
        'Gasolina / Diesel' => 'gasolina'
    ];

    $total_nuevos_gastos = 0;
    
    // Preparamos el insert usando parada_id y la ruta de la foto que es varchar(255)
    $stmtGasto = $conn->prepare("INSERT INTO gastos (ruta_id, parada_id, concepto, monto, foto, fecha) VALUES (?, ?, ?, ?, ?, NOW())");

    foreach ($conceptos_keys as $concepto_nombre => $key) {
        $monto = floatval($_POST["gasto_$key"] ?? 0);
        
        if ($monto > 0) {
            $total_nuevos_gastos += $monto;
            $ruta_foto_bd = null; // Si no hay foto, se inserta como NULL

            // Verificar si el chofer adjuntó una imagen para este gasto en específico
            if (isset($_FILES["foto_$key"]) && $_FILES["foto_$key"]['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["foto_$key"]['tmp_name'];
                $extension = strtolower(pathinfo($_FILES["foto_$key"]['name'], PATHINFO_EXTENSION));
                
                // Generar un nombre único para evitar que las fotos se sobreescriban
                $nuevo_nombre_archivo = "gasto_{$ruta_id}_{$parada_id}_{$key}_" . time() . "." . $extension;
                $destino_final = $upload_dir . $nuevo_nombre_archivo;
                
                if (move_uploaded_file($tmp_name, $destino_final)) {
                    // Guardamos la ruta relativa para la base de datos
                    $ruta_foto_bd = "uploads/gastos/" . $nuevo_nombre_archivo;
                }
            }

            $stmtGasto->bind_param("iisds", $ruta_id, $parada_id, $concepto_nombre, $monto, $ruta_foto_bd);
            $stmtGasto->execute();
        }
    }
    $stmtGasto->close();

    // 4. Actualizar total_gastos en la ruta solo si hubo gastos nuevos
    if ($total_nuevos_gastos > 0) {
        $stmtRutaUpdate = $conn->prepare("UPDATE rutas SET total_gastos = total_gastos + ? WHERE id = ?");
        $stmtRutaUpdate->bind_param("di", $total_nuevos_gastos, $ruta_id);
        $stmtRutaUpdate->execute();
        $stmtRutaUpdate->close();
    }

    // Confirmar todos los cambios
    $conn->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Parada completada y gastos registrados correctamente'
    ]);

} catch (mysqli_sql_exception $e) {
    // Si algo falla (ej. base de datos), revertimos todo
    $conn->rollback();
    error_log("Error de BD en agregar_parada_chofer: " . $e->getMessage());
    echo json_encode(['success' => false, 'mensaje' => '❌ Error interno al procesar la parada.']);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>

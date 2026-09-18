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

// Configuración de directorios para guardar archivos
$upload_dir_gastos = '../uploads/gastos/';
if (!is_dir($upload_dir_gastos)) {
    mkdir($upload_dir_gastos, 0777, true);
}

$upload_dir_cucas = '../uploads/cucas/';
if (!is_dir($upload_dir_cucas)) {
    mkdir($upload_dir_cucas, 0777, true);
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
    $stmtParada = $conn->prepare("UPDATE paradas SET estatus = 'completada', completada = 1, km_actual = ? WHERE id = ?");
    $stmtParada->bind_param("di", $km_actual, $parada_id);
    $stmtParada->execute();
    $stmtParada->close();

    // 3. Procesar Gastos y Fotos de Gastos
    $conceptos_keys = [
        'Hotel / Hospedaje' => 'hotel',
        'Caseta'            => 'caseta',
        'Comida / Alimentos'=> 'comida',
        'Estacionamiento'   => 'estacionamiento',
        'Gasolina / Diesel' => 'gasolina'
    ];

    $total_nuevos_gastos = 0;
    
    $stmtGasto = $conn->prepare("INSERT INTO gastos (ruta_id, parada_id, concepto, monto, foto, fecha) VALUES (?, ?, ?, ?, ?, NOW())");

    foreach ($conceptos_keys as $concepto_nombre => $key) {
        $monto = floatval($_POST["gasto_$key"] ?? 0);
        
        if ($monto > 0) {
            $total_nuevos_gastos += $monto;
            $ruta_foto_bd = null;

            if (isset($_FILES["foto_$key"]) && $_FILES["foto_$key"]['error'] === UPLOAD_ERR_OK) {
                $tmp_name = $_FILES["foto_$key"]['tmp_name'];
                $extension = strtolower(pathinfo($_FILES["foto_$key"]['name'], PATHINFO_EXTENSION));
                
                $nuevo_nombre_archivo = "gasto_{$ruta_id}_{$parada_id}_{$key}_" . time() . "." . ($extension ?: 'jpg');
                $destino_final = $upload_dir_gastos . $nuevo_nombre_archivo;
                
                if (move_uploaded_file($tmp_name, $destino_final)) {
                    $ruta_foto_bd = "uploads/gastos/" . $nuevo_nombre_archivo;
                }
            }

            $stmtGasto->bind_param("iisds", $ruta_id, $parada_id, $concepto_nombre, $monto, $ruta_foto_bd);
            $stmtGasto->execute();
        }
    }
    $stmtGasto->close();

    // 4. Procesar Cucas (Facturas) enviadas desde la app (Soporta múltiples fotos/páginas por cuca)
    $stmtCuca = $conn->prepare("INSERT INTO cucas (ruta_id, parada_id, numero_cuca, foto_cuca, fecha) VALUES (?, ?, ?, ?, NOW())");
    
    $i = 0;
    while (isset($_POST["numero_cuca_$i"])) {
        $numero_cuca = trim($_POST["numero_cuca_$i"]);
        
        if (!empty($numero_cuca)) {
            $fotos_procesadas = 0;

            // Opción A: Múltiples fotos enviadas como arreglo (foto_cuca_0_0, foto_cuca_0_1, etc.)
            $j = 0;
            while (isset($_FILES["foto_cuca_{$i}_{$j}"])) {
                if ($_FILES["foto_cuca_{$i}_{$j}"]['error'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES["foto_cuca_{$i}_{$j}"]['tmp_name'];
                    $extension = strtolower(pathinfo($_FILES["foto_cuca_{$i}_{$j}"]['name'], PATHINFO_EXTENSION));
                    
                    $nuevo_nombre_cuca = "cuca_{$ruta_id}_{$parada_id}_" . time() . "_{$i}_pag{$j}." . ($extension ?: 'jpg');
                    $destino_cuca = $upload_dir_cucas . $nuevo_nombre_cuca;
                    
                    if (move_uploaded_file($tmp_name, $destino_cuca)) {
                        $ruta_foto_cuca_bd = "uploads/cucas/" . $nuevo_nombre_cuca;
                        $stmtCuca->bind_param("iiss", $ruta_id, $parada_id, $numero_cuca, $ruta_foto_cuca_bd);
                        $stmtCuca->execute();
                        $fotos_procesadas++;
                    }
                }
                $j++;
            }

            // Opción B: Foto única por retrocompatibilidad (foto_cuca_0)
            if ($fotos_procesadas === 0 && isset($_FILES["foto_cuca_$i"]) && $_FILES["foto_cuca_$i"]['error'] === UPLOAD_ERR_OK) {
                $tmp_name_cuca = $_FILES["foto_cuca_$i"]['tmp_name'];
                $extension_cuca = strtolower(pathinfo($_FILES["foto_cuca_$i"]['name'], PATHINFO_EXTENSION));
                
                $nuevo_nombre_cuca = "cuca_{$ruta_id}_{$parada_id}_" . time() . "_{$i}." . ($extension_cuca ?: 'jpg');
                $destino_final_cuca = $upload_dir_cucas . $nuevo_nombre_cuca;
                
                if (move_uploaded_file($tmp_name_cuca, $destino_final_cuca)) {
                    $ruta_foto_cuca_bd = "uploads/cucas/" . $nuevo_nombre_cuca;
                    $stmtCuca->bind_param("iiss", $ruta_id, $parada_id, $numero_cuca, $ruta_foto_cuca_bd);
                    $stmtCuca->execute();
                    $fotos_procesadas++;
                }
            }

            // Opción C: Cuca sin foto adjunta
            if ($fotos_procesadas === 0) {
                $ruta_foto_cuca_bd = "";
                $stmtCuca->bind_param("iiss", $ruta_id, $parada_id, $numero_cuca, $ruta_foto_cuca_bd);
                $stmtCuca->execute();
            }
        }
        $i++;
    }
    $stmtCuca->close();

    // 5. Actualizar total_gastos en la ruta solo si hubo gastos nuevos
    if ($total_nuevos_gastos > 0) {
        $stmtRutaUpdate = $conn->prepare("UPDATE rutas SET total_gastos = total_gastos + ? WHERE id = ?");
        $stmtRutaUpdate->bind_param("di", $total_nuevos_gastos, $ruta_id);
        $stmtRutaUpdate->execute();
        $stmtRutaUpdate->close();
    }

    // Confirmar la transacción
    $conn->commit();

    echo json_encode([
        'success' => true,
        'mensaje' => '✅ Parada completada, gastos y cucas registradas correctamente'
    ]);

} catch (mysqli_sql_exception $e) {
    $conn->rollback();
    error_log("Error de BD en agregar_parada_chofer: " . $e->getMessage());
    echo json_encode(['success' => false, 'mensaje' => '❌ Error interno al procesar la parada.']);
} finally {
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
}
?>

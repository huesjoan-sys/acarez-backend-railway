<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

// 1. Ajuste de ruta a la raíz
require_once '../conexion.php';

$metodo = $_SERVER['REQUEST_METHOD'];

// =======================================================
// 1. POST: GUARDAR GASTO
// =======================================================
if ($metodo === 'POST') {
    $ruta_id  = intval($_POST['ruta_id'] ?? 0);
    $concepto = trim($_POST['concepto'] ?? '');
    $monto    = floatval($_POST['monto'] ?? 0);

    if ($ruta_id <= 0 || empty($concepto) || $monto <= 0) {
        $input = json_decode(file_get_contents('php://input'), true);
        if ($input) {
            $ruta_id  = intval($input['ruta_id'] ?? 0);
            $concepto = trim($input['concepto'] ?? '');
            $monto    = floatval($input['monto'] ?? 0);
        }
    }

    if ($ruta_id <= 0 || empty($concepto) || $monto <= 0) {
        echo json_encode(['success' => false, 'mensaje' => '⚠️ Faltan datos obligatorios.']);
        exit;
    }

    $foto_path = '';
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        // 2. Ajuste de ruta para uploads fuera de api/
        $dir_disco = '../uploads/gastos/';
        if (!file_exists($dir_disco)) mkdir($dir_disco, 0777, true);
        
        $nombre_archivo = time() . '_' . basename($_FILES['foto']['name']);
        $foto_path = 'uploads/gastos/' . $nombre_archivo;
        move_uploaded_file($_FILES['foto']['tmp_name'], '../' . $foto_path);
    }

    $stmt = $conn->prepare("INSERT INTO gastos (ruta_id, concepto, monto, foto, fecha) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("isds", $ruta_id, $concepto, $monto, $foto_path);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'mensaje' => '✅ Gasto registrado', 'id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'mensaje' => '❌ Error BD: ' . $conn->error]);
    }
    $stmt->close();
    exit;
}

// =======================================================
// 2. GET: OBTENER GASTOS DE UNA RUTA
// =======================================================
if ($metodo === 'GET') {
    $ruta_id = intval($_GET['ruta_id'] ?? 0);
    
    if ($ruta_id <= 0) {
        echo json_encode(['success' => false, 'mensaje' => 'Ruta no válida']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id, concepto, monto, foto, fecha FROM gastos WHERE ruta_id = ? ORDER BY id DESC");
    $stmt->bind_param("i", $ruta_id);
    $stmt->execute();
    $res = $stmt->get_result();

    $gastos = [];
    $total = 0.0;
    while ($row = $res->fetch_assoc()) {
        $monto = floatval($row['monto']);
        $total += $monto;
        $row['monto'] = $monto;
        $gastos[] = $row;
    }

    echo json_encode(['success' => true, 'gastos' => $gastos, 'total_gastos' => $total]);
    $stmt->close();
    exit;
}

$conn->close();
?>

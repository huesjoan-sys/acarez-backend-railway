<?php
// api/registrar_chofer_app.php
header('Content-Type: application/json');
require_once '../conexion.php';

$nombreChofer = trim($_POST['nombre'] ?? '');
$pin = trim($_POST['pin'] ?? '');

if (empty($nombreChofer) || empty($pin)) {
    echo json_encode(["success" => false, "mensaje" => "Ingresa tu nombre y un PIN numérico"]);
    exit;
}

try {
    // Verificar si el chofer ya existe en la tabla choferes
    $stmt = $pdo->prepare("SELECT id FROM choferes WHERE nombre_chofer = ?");
    $stmt->execute([$nombreChofer]);
    $existente = $stmt->fetch();

    if ($existente) {
        // Si ya existe, le actualizamos/registramos su PIN
        $stmtUpdate = $pdo->prepare("UPDATE choferes SET pin_password = ?, activo = 1 WHERE id = ?");
        $stmtUpdate->execute([$pin, $existente['id']]);
        
        echo json_encode([
            "success" => true,
            "mensaje" => "Registro actualizado",
            "chofer" => [
                "id" => $existente['id'],
                "usuario" => $nombreChofer,
                "nombre_completo" => $nombreChofer
            ]
        ]);
    } else {
        // Si es nuevo, lo creamos con valores predeterminados
        $stmtInsert = $pdo->prepare("INSERT INTO choferes (nombre_chofer, placas, numero_economico, pin_password, activo) VALUES (?, 'PENDIENTE', 'PENDIENTE', ?, 1)");
        $stmtInsert->execute([$nombreChofer, $pin]);
        $nuevoId = $pdo->lastInsertId();

        echo json_encode([
            "success" => true,
            "mensaje" => "Registro exitoso",
            "chofer" => [
                "id" => $nuevoId,
                "usuario" => $nombreChofer,
                "nombre_completo" => $nombreChofer
            ]
        ]);
    }
} catch (Exception $e) {
    echo json_encode(["success" => false, "mensaje" => "Error en el servidor: " . $e->getMessage()]);
}
?>

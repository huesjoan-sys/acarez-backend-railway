<?php
// usuarios_app_admin.php
require_once 'conexion.php';

// Manejar peticiones POST (Crear o Actualizar)
$mensaje = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $id = $_POST['id'] ?? '';
        $usuario = trim($_POST['nombre_usuario'] ?? '');
        $pin = trim($_POST['pin_password'] ?? '');
        $nombreCompleto = trim($_POST['nombre_completo'] ?? '');

        if (!empty($usuario) && !empty($pin) && !empty($nombreCompleto)) {
            try {
                if (!empty($id)) {
                    // Actualizar / Restablecer PIN
                    $stmt = $pdo->prepare("UPDATE usuarios_app SET nombre_usuario = ?, pin_password = ?, nombre_completo = ? WHERE id = ?");
                    $stmt->execute([$usuario, $pin, $nombreCompleto, $id]);
                    $mensaje = "Usuario actualizado correctamente.";
                } else {
                    // Crear nuevo chofer
                    $stmt = $pdo->prepare("INSERT INTO usuarios_app (nombre_usuario, pin_password, nombre_completo) VALUES (?, ?, ?)");
                    $stmt->execute([$usuario, $pin, $nombreCompleto]);
                    $mensaje = "Chofer registrado exitosamente.";
                }
            } catch (Exception $e) {
                $error = "Error: El usuario ya existe o hubo un fallo en la BD.";
            }
        } else {
            $error = "Todos los campos son obligatorios.";
        }
    } elseif ($accion === 'eliminar') {
        $id = $_POST['id'] ?? '';
        if (!empty($id)) {
            try {
                $stmt = $pdo->prepare("DELETE FROM usuarios_app WHERE id = ?");
                $stmt->execute([$id]);
                $mensaje = "Chofer eliminado del sistema.";
            } catch (Exception $e) {
                $error = "Error al eliminar el chofer.";
            }
        }
    }
}

// Obtener lista de usuarios
$stmt = $pdo->query("SELECT * FROM usuarios_app ORDER BY id DESC");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Usuarios App - Acarez</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-5">
        <h2 class="mb-4 text-purple" style="color: #4A148C;">📱 Gestión de Accesos App (Choferes)</h2>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success"><?= $mensaje ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Formulario para Registrar / Editar -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Registrar o Modificar Chofer</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="accion" value="guardar">
                    <input type="hidden" name="id" id="form-id">

                    <div class="col-md-4">
                        <label class="form-label">Nombre de Usuario (Login)</label>
                        <input type="text" class="form-control" name="nombre_usuario" id="form-usuario" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">PIN Numérico (Contraseña)</label>
                        <input type="text" class="form-control" name="pin_password" id="form-pin" maxlength="6" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" name="nombre_completo" id="form-nombre" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100" style="background-color: #4A148C; border: none;">Guardar</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de Usuarios -->
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Choferes con Acceso a la App</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped mb-0 align-middle">
                        <thead class="table-dark" style="background-color: #4A148C;">
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>PIN (Contraseña)</th>
                                <th>Nombre Completo</th>
                                <th>Fecha Registro</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr><td colspan="6" class="text-center py-4">No hay choferes registrados en la app.</td></tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><strong><?= htmlspecialchars($u['nombre_usuario']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($u['pin_password']) ?></code></td>
                                        <td><?= htmlspecialchars($u['nombre_completo']) ?></td>
                                        <td><?= $u['fecha_creacion'] ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-warning" onclick="editarUsuario(<?= $u['id'] ?>, '<?= $u['nombre_usuario'] ?>', '<?= $u['pin_password'] ?>', '<?= htmlspecialchars($u['nombre_completo']) ?>')">Editar / PIN</button>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro de eliminar este acceso?');">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function editarUsuario(id, usuario, pin, nombre) {
            document.getElementById('form-id').value = id;
            document.getElementById('form-usuario').value = usuario;
            document.getElementById('form-pin').value = pin;
            document.getElementById('form-nombre').value = nombre;
            window.scrollTo({top: 0, behavior: 'smooth'});
        }
    </script>
</body>
</html>

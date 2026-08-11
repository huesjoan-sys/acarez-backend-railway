<?php
// ============================================
// ACAREZ - PANEL ADMINISTRATIVO COMPLETO
// ============================================

session_start();
require_once 'conexion.php';

$seccion = $_GET['seccion'] ?? 'reportes';
$accion = $_GET['accion'] ?? '';

// ==============================================
// 1. MANEJAR ACCIONES DE DESTINOS
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Agregar destino
    if (isset($_POST['agregar_destino'])) {
        $razon_social = trim($_POST['razon_social']);
        $sucursal = trim($_POST['sucursal']);
        $direccion = trim($_POST['direccion']);
        if (!empty($razon_social) && !empty($sucursal) && !empty($direccion)) {
            $stmt = $conn->prepare("INSERT INTO destinos (razon_social, sucursal, direccion) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $razon_social, $sucursal, $direccion);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Editar destino
    if (isset($_POST['editar_destino'])) {
        $id = intval($_POST['id']);
        $razon_social = trim($_POST['razon_social']);
        $sucursal = trim($_POST['sucursal']);
        $direccion = trim($_POST['direccion']);
        if (!empty($razon_social) && !empty($sucursal) && !empty($direccion)) {
            $stmt = $conn->prepare("UPDATE destinos SET razon_social = ?, sucursal = ?, direccion = ? WHERE id = ?");
            $stmt->bind_param("sssi", $razon_social, $sucursal, $direccion, $id);
            $stmt->execute();
            $stmt->close();
        }
    }
    // Eliminar destino
    if (isset($_POST['eliminar_destino'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM destinos WHERE id = $id");
    }
}

// ==============================================
// 2. FUNCIÓN PARA OBTENER DESTINOS
// ==============================================
function obtenerDestinos($conn) {
    return $conn->query("SELECT * FROM destinos WHERE activo = 1 ORDER BY razon_social ASC");
}

// ==============================================
// 3. FUNCIÓN PARA OBTENER RUTAS
// ==============================================
function obtenerRutas($conn, $filtros = []) {
    $where = "";
    if (!empty($filtros['chofer'])) {
        $where = "WHERE chofer LIKE '%" . $conn->real_escape_string($filtros['chofer']) . "%'";
    }
    if (!empty($filtros['estatus'])) {
        $where .= ($where ? " AND" : " WHERE") . " estatus = '" . $conn->real_escape_string($filtros['estatus']) . "'";
    }
    $sql = "SELECT * FROM rutas $where ORDER BY id DESC";
    return $conn->query($sql);
}

// ==============================================
// 4. OBTENER DATOS DE UNA RUTA CON SUS PARADAS
// ==============================================
if ($accion == 'get_ruta_data' && !empty($_GET['ruta_id'])) {
    header('Content-Type: application/json');
    $ruta_id = intval($_GET['ruta_id']);
    
    // Obtener datos de la ruta
    $ruta_sql = "SELECT * FROM rutas WHERE id = $ruta_id";
    $ruta_result = $conn->query($ruta_sql);
    $ruta = $ruta_result->fetch_assoc();
    
    // Obtener paradas
    $paradas_sql = "SELECT p.*, d.razon_social, d.sucursal 
                    FROM paradas p 
                    LEFT JOIN destinos d ON p.destino_id = d.id 
                    WHERE p.ruta_id = $ruta_id 
                    ORDER BY p.orden ASC";
    $paradas_result = $conn->query($paradas_sql);
    $paradas = [];
    while ($row = $paradas_result->fetch_assoc()) {
        $paradas[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'ruta' => $ruta,
        'paradas' => $paradas
    ]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ACAREZ - Panel Administrativo</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f0f2f5; }
        .header {
            background: #ffffff;
            height: 70px;
            display: flex;
            align-items: center;
            padding: 0 20px;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .menu-btn {
            background: #4A148C;
            color: white;
            border: none;
            width: 40px; height: 40px;
            border-radius: 8px;
            margin-right: 15px;
            cursor: pointer;
            font-size: 20px;
        }
        .logo-header {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo-header img { height: 50px; width: auto; }
        .sidebar {
            position: fixed;
            top: 0; left: -280px;
            width: 280px; height: 100%;
            background: #3C096C;
            color: white;
            transition: left 0.3s ease;
            z-index: 200;
            padding-top: 80px;
        }
        .sidebar.open { left: 0; }
        .sidebar a {
            display: block; padding: 15px 25px;
            color: white; text-decoration: none;
            border-left: 4px solid transparent;
        }
        .sidebar a:hover, .sidebar a.active {
            background: #6A1B9A;
            border-left-color: #ffc107;
        }
        .overlay {
            position: fixed; top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5); z-index: 150; display: none;
        }
        .overlay.show { display: block; }
        .main-content { margin-top: 80px; padding: 20px; }
        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4A148C; color: white; }
        .btn {
            padding: 8px 16px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-primary { background: #4A148C; color: white; }
        .btn-success { background: #6A1B9A; color: white; }
        .btn-warning { background: #ffc107; color: #222; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .form-inline { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
        .form-inline input, .form-inline select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .detalle-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 2000;
            display: none; align-items: center; justify-content: center;
        }
        .detalle-content {
            background: white; padding: 20px; border-radius: 15px;
            width: 95%; max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .cerrar-modal {
            position: absolute; top: 15px; right: 20px;
            font-size: 28px; cursor: pointer; color: #999;
        }
        .cerrar-modal:hover { color: #333; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-activa { background: #d4edda; color: #155724; }
        .badge-completada { background: #cce5ff; color: #004085; }
        .badge-cancelada { background: #f8d7da; color: #721c24; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) {
            .grid-2col { grid-template-columns: 1fr; }
            .logo-header img { height: 35px; }
        }
    </style>
</head>
<body>

<div class="header">
    <button class="menu-btn" onclick="toggleMenu()">☰</button>
    <div class="logo-header">
        <img src="imagenes/acarez_3.png" alt="Logo">
    </div>
</div>

<div class="sidebar" id="sidebar">
    <a href="?seccion=reportes" class="<?= $seccion == 'reportes' ? 'active' : '' ?>">📋 Viajes</a>
    <a href="?seccion=rutas" class="<?= $seccion == 'rutas' ? 'active' : '' ?>">🔄 Rutas</a>
    <a href="?seccion=destinos" class="<?= $seccion == 'destinos' ? 'active' : '' ?>">📍 Destinos</a>
    <a href="?seccion=catalogos" class="<?= $seccion == 'catalogos' ? 'active' : '' ?>">⚙️ Configuración</a>
</div>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div id="detalleModal" class="detalle-modal">
    <div class="detalle-content" id="modalBody">
        <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
        <p style="text-align:center;">Cargando detalles...</p>
    </div>
</div>

<div class="main-content">
    
    <?php if ($seccion == 'reportes'): ?>
        <!-- SECCIÓN VIAJES (EXISTENTE) -->
        <?php include 'panel_reportes.php'; ?>
        
    <?php elseif ($seccion == 'rutas'): ?>
        <!-- ============================================== -->
        <!-- SECCIÓN RUTAS                                  -->
        <!-- ============================================== -->
        <?php
        $filtro_chofer = $_GET['filtro_chofer'] ?? '';
        $filtro_estatus = $_GET['filtro_estatus'] ?? '';
        $filtros = [];
        if (!empty($filtro_chofer)) $filtros['chofer'] = $filtro_chofer;
        if (!empty($filtro_estatus)) $filtros['estatus'] = $filtro_estatus;
        $rutas = obtenerRutas($conn, $filtros);
        ?>
        
        <div class="card">
            <h2>🔄 Rutas de Viaje</h2>
            <form method="GET" class="form-inline">
                <input type="hidden" name="seccion" value="rutas">
                <input type="text" name="filtro_chofer" placeholder="Filtrar por chofer" value="<?= htmlspecialchars($filtro_chofer) ?>">
                <select name="filtro_estatus">
                    <option value="">-- Todos --</option>
                    <option value="activa" <?= $filtro_estatus == 'activa' ? 'selected' : '' ?>>Activa</option>
                    <option value="completada" <?= $filtro_estatus == 'completada' ? 'selected' : '' ?>>Completada</option>
                    <option value="cancelada" <?= $filtro_estatus == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="?seccion=rutas" class="btn btn-primary">Limpiar</a>
            </form>
            
            <?php if($rutas->num_rows == 0): ?>
                <p>No hay rutas registradas.</p>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Chofer</th>
                                <th>Placas</th>
                                <th>Origen</th>
                                <th>Destino Final</th>
                                <th>Km Total</th>
                                <th>Paradas</th>
                                <th>Total Gastos</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $rutas->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['chofer']) ?></td>
                                <td><?= htmlspecialchars($row['placas']) ?></td>
                                <td><?= htmlspecialchars($row['origen']) ?></td>
                                <td><?= htmlspecialchars($row['destino_final'] ?? '-') ?></td>
                                <td><?= number_format($row['km_total'] ?? 0, 0) ?> km</td>
                                <td><?= $row['numero_paradas'] ?></td>
                                <td>$<?= number_format($row['total_gastos'] ?? 0, 2) ?></td>
                                <td>
                                    <span class="badge badge-<?= $row['estatus'] ?>">
                                        <?= ucfirst($row['estatus']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info" onclick="verDetalleRuta(<?= $row['id'] ?>)">Ver Detalle</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($seccion == 'destinos'): ?>
        <!-- ============================================== -->
        <!-- SECCIÓN DESTINOS                              -->
        <!-- ============================================== -->
        <?php $destinos = obtenerDestinos($conn); ?>
        
        <div class="card">
            <h2>📍 Destinos (Clientes)</h2>
            <form method="POST" class="form-inline">
                <input type="text" name="razon_social" placeholder="Razón Social" required>
                <input type="text" name="sucursal" placeholder="Sucursal" required>
                <input type="text" name="direccion" placeholder="Dirección" required>
                <button type="submit" name="agregar_destino" class="btn btn-success">➕ Agregar</button>
            </form>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Razón Social</th>
                            <th>Sucursal</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $destinos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td id="ds_razon_<?= $row['id'] ?>"><?= htmlspecialchars($row['razon_social']) ?></td>
                            <td id="ds_sucursal_<?= $row['id'] ?>"><?= htmlspecialchars($row['sucursal']) ?></td>
                            <td id="ds_direccion_<?= $row['id'] ?>"><?= htmlspecialchars($row['direccion']) ?></td>
                            <td>
                                <button class="btn btn-warning" onclick="editarDestino(<?= $row['id'] ?>)">✏️</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="eliminar_destino" class="btn btn-danger" onclick="return confirm('¿Eliminar este destino?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Formulario oculto para editar destino -->
        <div id="editarDestinoForm" style="display:none; background:#f5f5f5; padding:15px; border-radius:10px; margin-top:10px;">
            <h3>Editar Destino</h3>
            <form method="POST" class="form-inline">
                <input type="hidden" name="id" id="edit_destino_id">
                <input type="text" name="razon_social" id="edit_razon_social" placeholder="Razón Social" required>
                <input type="text" name="sucursal" id="edit_sucursal" placeholder="Sucursal" required>
                <input type="text" name="direccion" id="edit_direccion" placeholder="Dirección" required>
                <button type="submit" name="editar_destino" class="btn btn-success">💾 Guardar</button>
                <button type="button" class="btn btn-danger" onclick="cancelarEditarDestino()">❌ Cancelar</button>
            </form>
        </div>
        
    <?php elseif ($seccion == 'catalogos'): ?>
        <!-- SECCIÓN CATÁLOGOS (EXISTENTE) -->
        <?php include 'panel_catalogos.php'; ?>
        
    <?php endif; ?>
    
</div>

<script>
// ============ FUNCIONES GENERALES ============
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
}

// ============ MODAL ============
function cerrarModal() {
    document.getElementById('detalleModal').style.display = 'none';
}

// ============ DETALLE DE RUTA ============
function verDetalleRuta(id) {
    const modal = document.getElementById('detalleModal');
    const body = document.getElementById('modalBody');
    body.innerHTML = '<span class="cerrar-modal" onclick="cerrarModal()">&times;</span><div style="text-align:center; padding:40px;">Cargando detalles...</div>';
    modal.style.display = 'flex';
    
    fetch('?accion=get_ruta_data&ruta_id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                body.innerHTML = '<span class="cerrar-modal" onclick="cerrarModal()">&times;</span><div style="padding:20px; color:red;">Error al cargar los datos</div>';
                return;
            }
            const r = data.ruta;
            const paradas = data.paradas || [];
            
            let paradasHtml = '';
            if (paradas.length === 0) {
                paradasHtml = '<p>No hay paradas registradas</p>';
            } else {
                paradasHtml = '<table><thead><tr><th>#</th><th>Destino</th><th>Gastos</th></tr></thead><tbody>';
                paradas.forEach(p => {
                    const destino = p.razon_social ? p.razon_social + ' - ' + p.sucursal : p.destino_manual || 'Manual';
                    const totalGasto = parseFloat(p.gasto_hotel || 0) + parseFloat(p.gasto_caseta || 0) + parseFloat(p.gasto_comida || 0) + parseFloat(p.gasto_estacionamiento || 0) + parseFloat(p.gasto_gasolina || 0);
                    paradasHtml += `<tr><td>${p.orden}</td><td>${destino}</td><td>$${totalGasto.toFixed(2)}</td></tr>`;
                });
                paradasHtml += '</tbody></table>';
            }
            
            body.innerHTML = `
                <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
                <h2 style="color:#4A148C;">📋 Detalle de Ruta #${r.id}</h2>
                <div style="background:#f5f5f5; padding:15px; border-radius:10px; margin:10px 0;">
                    <p><strong>Chofer:</strong> ${r.chofer}</p>
                    <p><strong>Vehículo:</strong> ${r.placas} | ${r.no_economico}</p>
                    <p><strong>Origen:</strong> ${r.origen}</p>
                    <p><strong>Destino Final:</strong> ${r.destino_final || 'Pendiente'}</p>
                    <p><strong>Km inicial:</strong> ${r.km_inicial} | <strong>Km final:</strong> ${r.km_final || 'Pendiente'}</p>
                    <p><strong>Km total:</strong> ${r.km_total || 0} km</p>
                    <p><strong>Paradas:</strong> ${r.numero_paradas}</p>
                    <p><strong>Total gastos:</strong> $${parseFloat(r.total_gastos || 0).toFixed(2)}</p>
                    <p><strong>Estatus:</strong> <span class="badge badge-${r.estatus}">${r.estatus}</span></p>
                    <p><strong>Inicio:</strong> ${r.fecha_inicio} ${r.fecha_fin ? '| Fin: ' + r.fecha_fin : ''}</p>
                </div>
                <h3>📍 Paradas</h3>
                ${paradasHtml}
            `;
        })
        .catch(err => {
            body.innerHTML = '<span class="cerrar-modal" onclick="cerrarModal()">&times;</span><div style="padding:20px; color:red;">Error al cargar los datos</div>';
        });
}

// ============ EDITAR DESTINO ============
function editarDestino(id) {
    const razon = document.getElementById('ds_razon_' + id).innerText;
    const sucursal = document.getElementById('ds_sucursal_' + id).innerText;
    const direccion = document.getElementById('ds_direccion_' + id).innerText;
    
    document.getElementById('edit_destino_id').value = id;
    document.getElementById('edit_razon_social').value = razon;
    document.getElementById('edit_sucursal').value = sucursal;
    document.getElementById('edit_direccion').value = direccion;
    document.getElementById('editarDestinoForm').style.display = 'block';
    document.getElementById('editarDestinoForm').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEditarDestino() {
    document.getElementById('editarDestinoForm').style.display = 'none';
}
</script>
</body>
</html>

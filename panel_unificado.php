<?php
// ============================================
// ACAREZ - PANEL ADMINISTRATIVO COMPLETO
// ============================================

session_start();
require_once 'conexion.php';

$seccion = $_GET['seccion'] ?? 'reportes';
$semana_seleccionada = $_GET['semana_facturar'] ?? '';
$accion = $_GET['accion'] ?? '';

// ==============================================
// 1. OBTENER DATOS DE LA SEMANA (para el modal de detalle)
// ==============================================
if ($accion == 'get_semana_data' && !empty($_GET['semana'])) {
    header('Content-Type: application/json');
    $semana = $_GET['semana'];
    $year = substr($semana, 0, 4);
    $week = substr($semana, 6);
    $fecha_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
    $fecha_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
    
    $sql = "SELECT id, fecha, chofer, placas, destino_ida, total_general, 
                   km_inicial, km_final, km_total 
            FROM viajes 
            WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin' 
            ORDER BY fecha";
    $result = $conn->query($sql);
    
    $viajes = [];
    $total_gastos_semana = 0;
    while ($row = $result->fetch_assoc()) {
        $gasto = floatval($row['total_general']);
        $total_gastos_semana += $gasto;
        $viajes[] = [
            'id' => $row['id'],
            'fecha' => date('d/m/Y', strtotime($row['fecha'])),
            'chofer' => $row['chofer'],
            'placas' => $row['placas'],
            'destino' => $row['destino_ida'],
            'gasto' => number_format($gasto, 2),
            'km_inicial' => isset($row['km_inicial']) ? $row['km_inicial'] : 0,
            'km_final'   => isset($row['km_final']) ? $row['km_final'] : 0,
            'km_total'   => isset($row['km_total']) ? $row['km_total'] : 0
        ];
    }
    
    echo json_encode([
        'semana' => $semana,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'viajes' => $viajes,
        'total_gastos_semana' => number_format($total_gastos_semana, 2)
    ]);
    exit;
}

// ==============================================
// 2. OBTENER DATOS DE RUTA CON PARADAS
// ==============================================
if ($accion == 'get_ruta_data' && !empty($_GET['ruta_id'])) {
    header('Content-Type: application/json');
    $ruta_id = intval($_GET['ruta_id']);
    
    $ruta_sql = "SELECT * FROM rutas WHERE id = $ruta_id";
    $ruta_result = $conn->query($ruta_sql);
    $ruta = $ruta_result->fetch_assoc();
    
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

// ==============================================
// FUNCIONES DE APOYO
// ==============================================
function obtenerReportes($conn, $filtros = []) {
    $where = "";
    if (!empty($filtros['semana'])) {
        $year = substr($filtros['semana'], 0, 4);
        $week = substr($filtros['semana'], 6);
        $fecha_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
        $fecha_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
        $where = "WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
    } elseif (!empty($filtros['fecha_inicio']) && !empty($filtros['fecha_fin'])) {
        $where = "WHERE DATE(fecha) BETWEEN '{$filtros['fecha_inicio']}' AND '{$filtros['fecha_fin']}'";
    }
    $sql = "SELECT * FROM viajes $where ORDER BY fecha ASC";
    return $conn->query($sql);
}

function obtenerSemanasDisponibles($conn) {
    return $conn->query("SELECT DISTINCT CONCAT(YEAR(fecha), '-W', LPAD(WEEK(fecha, 1), 2, '0')) as semana, MIN(DATE(fecha)) as inicio, MAX(DATE(fecha)) as fin FROM viajes GROUP BY semana ORDER BY semana DESC");
}

function obtenerDestinos($conn) {
    return $conn->query("SELECT * FROM destinos WHERE activo = 1 ORDER BY razon_social ASC");
}

function obtenerRutas($conn, $filtros = []) {
    $where = "1=1";
    if (!empty($filtros['chofer'])) {
        $where .= " AND chofer LIKE '%" . $conn->real_escape_string($filtros['chofer']) . "%'";
    }
    if (!empty($filtros['estatus'])) {
        $where .= " AND estatus = '" . $conn->real_escape_string($filtros['estatus']) . "'";
    }
    $sql = "SELECT * FROM rutas WHERE $where ORDER BY id DESC";
    return $conn->query($sql);
}

// ==============================================
// FUNCIÓN PARA NORMALIZAR NÚMERO ECONÓMICO
// ==============================================
function normalizarNoEconomico($no) {
    $no = trim($no);
    if (preg_match('/^([A-Z])(\d+)$/', $no, $matches)) {
        return $matches[1] . '-' . $matches[2];
    }
    if (preg_match('/^[A-Z]-\d+$/', $no)) {
        return $no;
    }
    return null;
}

function manejarCatalogos($conn) {
    // Solo obtener datos, no procesar POST (se procesa arriba)
    return [
        'placas' => $conn->query("SELECT * FROM catalogo_placas ORDER BY id ASC"),
        'no_economicos' => $conn->query("SELECT * FROM catalogo_no_economico ORDER BY id ASC"),
        'choferes' => $conn->query("SELECT id, nombre_chofer, placas, numero_economico FROM choferes ORDER BY nombre_chofer ASC")
    ];
}

// ==============================================
// 5. PROCESAR POST (con redirecciones para evitar duplicados)
// ==============================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // ========== PLACAS ==========
    if (isset($_POST['agregar_placa'])) {
        $placa = trim($_POST['placa']);
        if (!empty($placa)) $conn->query("INSERT INTO catalogo_placas (placa) VALUES ('$placa')");
        header("Location: ?seccion=catalogos");
        exit;
    }
    if (isset($_POST['eliminar_placa'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM catalogo_placas WHERE id = $id");
        header("Location: ?seccion=catalogos");
        exit;
    }
    if (isset($_POST['editar_placa'])) {
        $id = intval($_POST['id']); $placa = trim($_POST['placa']);
        $conn->query("UPDATE catalogo_placas SET placa = '$placa' WHERE id = $id");
        header("Location: ?seccion=catalogos");
        exit;
    }
    
    // ========== NÚMEROS ECONÓMICOS ==========
    if (isset($_POST['agregar_no_economico'])) {
        $no = trim($_POST['no_economico']);
        $no_normalizado = normalizarNoEconomico($no);
        if ($no_normalizado !== null && !empty($no_normalizado)) {
            $conn->query("INSERT INTO catalogo_no_economico (no_economico) VALUES ('$no_normalizado')");
        }
        header("Location: ?seccion=catalogos");
        exit;
    }
    if (isset($_POST['eliminar_no_economico'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM catalogo_no_economico WHERE id = $id");
        header("Location: ?seccion=catalogos");
        exit;
    }
    if (isset($_POST['editar_no_economico'])) {
        $id = intval($_POST['id']);
        $no = trim($_POST['no_economico']);
        $no_normalizado = normalizarNoEconomico($no);
        if ($no_normalizado !== null && !empty($no_normalizado)) {
            $conn->query("UPDATE catalogo_no_economico SET no_economico = '$no_normalizado' WHERE id = $id");
        }
        header("Location: ?seccion=catalogos");
        exit;
    }
    
    // ========== CHOFERES ==========
    if (isset($_POST['agregar_chofer'])) {
        $nombre = trim($_POST['nombre_chofer']);
        $placas = trim($_POST['placas_chofer']);
        $no_economico = trim($_POST['numero_economico_chofer']);
        $no_normalizado = normalizarNoEconomico($no_economico);
        if (!empty($nombre) && !empty($placas) && $no_normalizado !== null && !empty($no_normalizado)) {
            $stmt = $conn->prepare("INSERT INTO choferes (nombre_chofer, placas, numero_economico) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $nombre, $placas, $no_normalizado);
            $stmt->execute();
            $stmt->close();
        }
        header("Location: ?seccion=catalogos");
        exit;
    }
    if (isset($_POST['eliminar_chofer'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM choferes WHERE id = $id");
        header("Location: ?seccion=catalogos");
        exit;
    }
    
    // ========== DESTINOS ==========
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
        header("Location: ?seccion=destinos");
        exit;
    }
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
        header("Location: ?seccion=destinos");
        exit;
    }
    if (isset($_POST['eliminar_destino'])) {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM destinos WHERE id = $id");
        header("Location: ?seccion=destinos");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ACAREZ - Panel Administrativo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .tabla-catalogo th, .tabla-catalogo td { padding: 6px 12px; line-height: 1.4; vertical-align: middle; }
        .btn {
            padding: 8px 16px; border: none; border-radius: 6px;
            cursor: pointer; font-weight: bold; transition: 0.3s;
        }
        .btn-primary { background: #4A148C; color: white; }
        .btn-success { background: #6A1B9A; color: white; }
        .btn-info { background: #17a2b8; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #222; }
        .form-inline { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
        .form-inline input, .form-inline select { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        #logoFijo {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 80px; height: 80px;
            z-index: 1000;
            cursor: pointer;
        }
        #logoFijo img { width: 100%; height: 100%; object-fit: contain; }
        .girar-logo {
            animation: girarInfinito 5s linear;
        }
        @keyframes girarInfinito {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .detalle-modal {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.85); z-index: 2000;
            display: none; align-items: center; justify-content: center;
        }
        .detalle-content {
            background: white; padding: 20px; border-radius: 15px;
            width: 95%; max-width: 1400px;
            max-height: 92vh;
            overflow-y: auto; position: relative;
        }
        .cerrar-modal {
            position: absolute; top: 15px; right: 20px;
            font-size: 28px; cursor: pointer; color: #999;
        }
        .cerrar-modal:hover { color: #333; }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 15px 0; }
        .seccion-viaje {
            background: #f8f9fa; padding: 12px; border-radius: 10px;
            border-top: 4px solid #4A148C;
        }
        .seccion-viaje-regreso { border-top-color: #6A1B9A; }
        .grupo-fotos {
            display: flex; flex-wrap: wrap; gap: 10px;
            margin: 10px 0; justify-content: center;
        }
        .grupo-fotos img {
            width: 80px; height: 80px;
            object-fit: cover; border-radius: 8px;
            cursor: pointer; border: 1px solid #ddd;
        }
        .resumen-dia { background-color: #f0f0f0; font-weight: bold; }
        .tabla-semana { min-width: 1000px; white-space: nowrap; }
        .tabla-semana th, .tabla-semana td { white-space: nowrap; padding: 8px 12px; }
        .btn-pequeno { padding: 4px 10px; font-size: 12px; }
        .error-msg { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .success-msg { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 10px; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-programada { background: #fff3cd; color: #856404; }
        .badge-activa { background: #d4edda; color: #155724; }
        .badge-completada { background: #cce5ff; color: #004085; }
        .badge-cancelada { background: #f8d7da; color: #721c24; }
        .seleccion-destinos {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 8px;
            background: #fafafa;
        }
        .seleccion-destinos label {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 4px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 13px;
            cursor: pointer;
        }
        .seleccion-destinos label:hover {
            background: #f0f0f0;
        }
        .seleccion-destinos input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #4A148C;
        }
        @media (max-width: 768px) {
            .grid-2col { grid-template-columns: 1fr; }
            #logoFijo { width: 60px; height: 60px; }
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
    <a href="?seccion=reportes" class="<?= $seccion == 'reportes' ? 'active' : '' ?>">📋 Viajes y Gastos</a>
    <a href="?seccion=rutas" class="<?= $seccion == 'rutas' ? 'active' : '' ?>">🔄 Rutas</a>
    <a href="?seccion=destinos" class="<?= $seccion == 'destinos' ? 'active' : '' ?>">📍 Destinos</a>
    <a href="?seccion=catalogos" class="<?= $seccion == 'catalogos' ? 'active' : '' ?>">⚙️ Configuración</a>
</div>

<div class="overlay" id="overlay" onclick="toggleMenu()"></div>

<div id="logoFijo">
    <img src="imagenes/acarez_2.png" alt="Logo Acarez">
</div>

<div id="detalleModal" class="detalle-modal">
    <div class="detalle-content" id="modalBody">
        <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
        <p style="text-align:center;">Cargando detalles...</p>
    </div>
</div>

<div id="semanaModal" class="detalle-modal" style="display:none;">
    <div class="detalle-content" style="max-width: 1400px;">
        <span class="cerrar-modal" onclick="cerrarSemanaModal()">&times;</span>
        <h2 style="color:#4A148C;">📊 Detalle de Viajes por Semana</h2>
        <div id="semanaDetalleContent"></div>
        <div style="margin-top:20px; text-align:right; display:flex; justify-content:flex-end; gap:10px;">
            <button class="btn btn-primary" onclick="cerrarSemanaModal()">Cerrar</button>
        </div>
    </div>
</div>

<div id="imageModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.9); z-index:3000; align-items:center; justify-content:center;" onclick="cerrarImageModal()">
    <span style="position:absolute; top:20px; right:35px; color:white; font-size:40px; cursor:pointer;">&times;</span>
    <img id="modalImage" style="max-width:90%; max-height:90%; border-radius:10px;">
</div>

<div class="main-content">
    
    <?php if ($seccion == 'reportes'): ?>
        <!-- ========== SECCIÓN REPORTES (VIAJES) ========== -->
        <?php
        $semana = $_GET['semana'] ?? '';
        $fecha_inicio = $_GET['fecha_inicio'] ?? '';
        $fecha_fin = $_GET['fecha_fin'] ?? '';
        $filtros = ['semana' => $semana, 'fecha_inicio' => $fecha_inicio, 'fecha_fin' => $fecha_fin];
        $reportes = obtenerReportes($conn, $filtros);
        $semanas = obtenerSemanasDisponibles($conn);
        ?>
        
        <div class="card">
            <form method="GET" class="form-inline" id="filtroForm">
                <input type="hidden" name="seccion" value="reportes">
                <select name="semana" id="semanaSelect">
                    <option value="">-- Filtrar por semana --</option>
                    <?php while($row = $semanas->fetch_assoc()): ?>
                        <option value="<?= $row['semana'] ?>" <?= ($semana == $row['semana']) ? 'selected' : '' ?>>
                            Semana <?= substr($row['semana'], -2) ?> (<?= $row['inicio'] ?> al <?= $row['fin'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>">
                <span>a</span>
                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="?seccion=reportes" class="btn btn-primary">Limpiar</a>
                <button type="button" class="btn btn-success" onclick="exportarExcel()">Exportar Excel</button>
                <button type="button" class="btn btn-info" onclick="exportarCSV()">📱 Exportar CSV (Celular)</button>
                <button type="button" class="btn btn-danger" onclick="exportarPDF()">📄 Exportar PDF</button>
                <button type="button" class="btn btn-success" id="btnDetalleSemana" style="background:#17a2b8; color:white;">📋 Detalle Semana</button>
            </form>
        </div>
        
        <?php if($reportes->num_rows == 0): ?>
            <div class="card">No hay reportes para los filtros seleccionados.</div>
        <?php else: ?>
            <div class="card">
                <div style="overflow-x: auto;">
                    <table id="tablaReportes">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Chofer</th>
                                <th>Placas</th>
                                <th>No. Eco</th>
                                <th>Km Inicial</th>
                                <th>Km Final</th>
                                <th>Km Recorrido</th>
                                <th>Total Gastos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $fecha_actual = '';
                        $suma_km_dia = 0;
                        while($row = $reportes->fetch_assoc()): 
                            $km_inicial = isset($row['km_inicial']) ? floatval($row['km_inicial']) : 0;
                            $km_final   = isset($row['km_final']) ? floatval($row['km_final']) : 0;
                            $km_total   = isset($row['km_total']) ? floatval($row['km_total']) : 0;
                            $fecha_row = date('Y-m-d', strtotime($row['fecha']));
                            
                            if ($fecha_actual != '' && $fecha_actual != $fecha_row) {
                                echo '<tr class="resumen-dia">
                                        <td colspan="8" style="text-align:right;">Total Km del día ' . date('d/m/Y', strtotime($fecha_actual)) . ':</td>
                                        <td colspan="1">' . number_format($suma_km_dia, 0) . ' km</td>
                                        <td colspan="1"></td>
                                      </tr>';
                                $suma_km_dia = 0;
                            }
                            $fecha_actual = $fecha_row;
                            $suma_km_dia += $km_total;
                        ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></td>
                                <td><?= htmlspecialchars($row['chofer']) ?></td>
                                <td><?= htmlspecialchars($row['placas']) ?></td>
                                <td><?= htmlspecialchars($row['no_economico']) ?></td>
                                <td><?= number_format($km_inicial, 0) ?></td>
                                <td><?= number_format($km_final, 0) ?></td>
                                <td><?= number_format($km_total, 0) ?> km</td>
                                <td style="font-weight: bold; color: #4A148C;">$<?= number_format($row['total_general'], 2) ?></td>
                                <td>
                                    <button class="btn btn-primary btn-pequeno" onclick="verDetalle(<?= $row['id'] ?>)">Ver</button>
                                    <button class="btn btn-danger btn-pequeno" onclick="eliminarViaje(<?= $row['id'] ?>)">Eliminar</button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        <?php if ($fecha_actual != ''): ?>
                            <tr class="resumen-dia">
                                <td colspan="8" style="text-align:right;">Total Km del día <?= date('d/m/Y', strtotime($fecha_actual)) ?>:</td>
                                <td colspan="1"><?= number_format($suma_km_dia, 0) ?> km</td>
                                <td colspan="1"></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ($seccion == 'rutas'): ?>
        <!-- ========== SECCIÓN RUTAS ========== -->
        <?php
        // Procesar creación de ruta
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_ruta'])) {
            $chofer_id = intval($_POST['chofer_id']);
            $fecha_ruta = $_POST['fecha_ruta'] ?? date('Y-m-d');
            $destinos_seleccionados = $_POST['destinos'] ?? [];
            
            if ($chofer_id > 0 && !empty($destinos_seleccionados)) {
                // Obtener datos del chofer
                $chofer_sql = "SELECT nombre_chofer, placas, numero_economico FROM choferes WHERE id = $chofer_id AND activo = 1";
                $chofer_result = $conn->query($chofer_sql);
                $chofer = $chofer_result->fetch_assoc();
                
                if ($chofer) {
                    // Insertar ruta
                    $fecha_inicio = $fecha_ruta . ' 00:00:00';
                    $stmt = $conn->prepare("INSERT INTO rutas (chofer, placas, no_economico, origen, fecha_inicio, km_inicial, estatus) VALUES (?, ?, ?, ?, ?, 0, 'programada')");
                    $origen = 'Pendiente';
                    $stmt->bind_param("sssss", $chofer['nombre_chofer'], $chofer['placas'], $chofer['numero_economico'], $origen, $fecha_inicio);
                    $stmt->execute();
                    $ruta_id = $conn->insert_id;
                    $stmt->close();
                    
                    // Insertar paradas (destinos)
                    $orden = 0;
                    foreach ($destinos_seleccionados as $destino_id) {
                        $orden++;
                        $stmt = $conn->prepare("INSERT INTO paradas (ruta_id, orden, destino_id, km_actual) VALUES (?, ?, ?, NULL)");
                        $stmt->bind_param("iii", $ruta_id, $orden, $destino_id);
                        $stmt->execute();
                        $stmt->close();
                    }
                    
                    // Actualizar número de paradas en la ruta
                    $conn->query("UPDATE rutas SET numero_paradas = $orden WHERE id = $ruta_id");
                    
                    echo '<div class="success-msg">✅ Ruta creada correctamente con ' . $orden . ' destinos.</div>';
                }
            } else {
                echo '<div class="error-msg">❌ Debes seleccionar un chofer y al menos un destino.</div>';
            }
        }
        
        // Procesar eliminación de ruta
        if (isset($_GET['eliminar_ruta']) && is_numeric($_GET['eliminar_ruta'])) {
            $ruta_id = intval($_GET['eliminar_ruta']);
            $conn->query("DELETE FROM paradas WHERE ruta_id = $ruta_id");
            $conn->query("DELETE FROM rutas WHERE id = $ruta_id");
            echo '<div class="success-msg">✅ Ruta eliminada correctamente.</div>';
        }
        
        // Obtener choferes activos
        $choferes = $conn->query("SELECT id, nombre_chofer, placas, numero_economico FROM choferes WHERE activo = 1 ORDER BY nombre_chofer ASC");
        
        // Obtener destinos activos
        $destinos = $conn->query("SELECT id, razon_social, sucursal, direccion FROM destinos WHERE activo = 1 ORDER BY razon_social ASC");
        
        // Obtener rutas con filtros
        $filtro_chofer = $_GET['filtro_chofer'] ?? '';
        $filtro_estatus = $_GET['filtro_estatus'] ?? '';
        $filtros = [];
        if (!empty($filtro_chofer)) $filtros['chofer'] = $filtro_chofer;
        if (!empty($filtro_estatus)) $filtros['estatus'] = $filtro_estatus;
        $rutas = obtenerRutas($conn, $filtros);
        ?>
        
        <!-- ========== FORMULARIO CREAR RUTA ========== -->
        <div class="card">
            <h2>➕ Crear Nueva Ruta</h2>
            <form method="POST" class="form-inline" style="flex-wrap: wrap; gap: 10px;">
                <select name="chofer_id" required style="min-width: 200px;">
                    <option value="">-- Seleccionar Chofer --</option>
                    <?php while($row = $choferes->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre_chofer']) ?> (<?= $row['placas'] ?>)</option>
                    <?php endwhile; ?>
                </select>
                
                <input type="date" name="fecha_ruta" value="<?= date('Y-m-d') ?>" required>
                
                <button type="submit" name="crear_ruta" class="btn btn-success">➕ Crear Ruta</button>
            </form>
            
            <div style="margin-top: 15px;">
                <p><strong>Selecciona los destinos para esta ruta:</strong> <span style="color:#666; font-size:12px;">(marca los clientes que debe visitar el chofer)</span></p>
                <div class="seleccion-destinos">
                    <?php if($destinos->num_rows == 0): ?>
                        <p style="color: #999; text-align:center; padding:20px;">No hay destinos registrados. Ve a la sección <strong>Destinos</strong> para agregar.</p>
                    <?php else: ?>
                        <?php while($row = $destinos->fetch_assoc()): ?>
                            <label>
                                <input type="checkbox" name="destinos[]" value="<?= $row['id'] ?>">
                                <span><?= htmlspecialchars($row['razon_social']) ?> - <?= htmlspecialchars($row['sucursal']) ?></span>
                                <span style="color:#999; font-size:11px; margin-left:auto;"><?= htmlspecialchars($row['direccion']) ?></span>
                            </label>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
                <div style="margin-top:10px; font-size:12px; color:#666;">
                    <span id="contadorDestinos">0</span> destinos seleccionados
                </div>
            </div>
        </div>
        
        <!-- ========== LISTADO DE RUTAS ========== -->
        <div class="card">
            <h2>🔄 Rutas Programadas</h2>
            <form method="GET" class="form-inline">
                <input type="hidden" name="seccion" value="rutas">
                <input type="text" name="filtro_chofer" placeholder="Filtrar por chofer" value="<?= htmlspecialchars($filtro_chofer) ?>">
                <select name="filtro_estatus">
                    <option value="">-- Todos --</option>
                    <option value="programada" <?= $filtro_estatus == 'programada' ? 'selected' : '' ?>>Programada</option>
                    <option value="activa" <?= $filtro_estatus == 'activa' ? 'selected' : '' ?>>Activa</option>
                    <option value="completada" <?= $filtro_estatus == 'completada' ? 'selected' : '' ?>>Completada</option>
                    <option value="cancelada" <?= $filtro_estatus == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                </select>
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="?seccion=rutas" class="btn btn-primary">Limpiar</a>
            </form>
            
            <?php if($rutas->num_rows == 0): ?>
                <p style="margin-top:15px;">No hay rutas registradas.</p>
            <?php else: ?>
                <div style="overflow-x: auto; margin-top:15px;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Chofer</th>
                                <th>Placas</th>
                                <th>Fecha</th>
                                <th>Destinos</th>
                                <th>Km Total</th>
                                <th>Gastos</th>
                                <th>Estatus</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $rutas->fetch_assoc()): 
                                // Obtener destinos de la ruta
                                $destinos_ruta = $conn->query("
                                    SELECT p.*, d.razon_social, d.sucursal 
                                    FROM paradas p 
                                    LEFT JOIN destinos d ON p.destino_id = d.id 
                                    WHERE p.ruta_id = {$row['id']} 
                                    ORDER BY p.orden ASC
                                ");
                                $destinos_list = [];
                                while($d = $destinos_ruta->fetch_assoc()) {
                                    $nombre = $d['razon_social'] ?? $d['destino_manual'] ?? 'Destino';
                                    $destinos_list[] = $nombre;
                                }
                                $destinos_text = implode(', ', array_slice($destinos_list, 0, 3));
                                if (count($destinos_list) > 3) $destinos_text .= ' +' . (count($destinos_list) - 3) . ' más';
                            ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td><?= htmlspecialchars($row['chofer']) ?></td>
                                <td><?= htmlspecialchars($row['placas']) ?></td>
                                <td><?= date('d/m/Y', strtotime($row['fecha_inicio'])) ?></td>
                                <td><?= $destinos_text ?></td>
                                <td><?= number_format($row['km_total'] ?? 0, 0) ?> km</td>
                                <td>$<?= number_format($row['total_gastos'] ?? 0, 2) ?></td>
                                <td>
                                    <span class="badge badge-<?= $row['estatus'] ?>">
                                        <?= ucfirst($row['estatus']) ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-info btn-pequeno" onclick="verDetalleRuta(<?= $row['id'] ?>)">Ver Detalle</button>
                                    <a href="?seccion=rutas&eliminar_ruta=<?= $row['id'] ?>" 
                                       class="btn btn-danger btn-pequeno" 
                                       onclick="return confirm('¿Eliminar esta ruta y todas sus paradas?')">🗑️</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
    <?php elseif ($seccion == 'destinos'): ?>
        <!-- ========== SECCIÓN DESTINOS ========== -->
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
                        <tr><th>ID</th><th>Razón Social</th><th>Sucursal</th><th>Dirección</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $destinos->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td id="ds_razon_<?= $row['id'] ?>"><?= htmlspecialchars($row['razon_social']) ?></td>
                            <td id="ds_sucursal_<?= $row['id'] ?>"><?= htmlspecialchars($row['sucursal']) ?></td>
                            <td id="ds_direccion_<?= $row['id'] ?>"><?= htmlspecialchars($row['direccion']) ?></td>
                            <td>
                                <button class="btn btn-warning btn-pequeno" onclick="editarDestino(<?= $row['id'] ?>)">✏️</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="eliminar_destino" class="btn btn-danger btn-pequeno" onclick="return confirm('¿Eliminar este destino?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
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
        <!-- ========== SECCIÓN CATÁLOGOS ========== -->
        <?php 
        $catalogos = manejarCatalogos($conn);
        if (isset($_SESSION['error_catalogo'])) {
            echo '<div class="error-msg">' . $_SESSION['error_catalogo'] . '</div>';
            unset($_SESSION['error_catalogo']);
        }
        if (isset($_SESSION['error_chofer'])) {
            echo '<div class="error-msg">' . $_SESSION['error_chofer'] . '</div>';
            unset($_SESSION['error_chofer']);
        }
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="card">
                <h2>📋 Placas</h2>
                <form method="POST" class="form-inline">
                    <input type="text" name="placa" placeholder="Nueva placa" required>
                    <button type="submit" name="agregar_placa" class="btn btn-success">➕ Agregar</button>
                </form>
                <table class="tabla-catalogo">
                    <thead><tr><th>ID</th><th>Placa</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php while($row = $catalogos['placas']->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td id="placa_<?= $row['id'] ?>"><?= htmlspecialchars($row['placa']) ?></td>
                            <td><button class="btn btn-warning btn-pequeno" onclick="editarPlaca(<?= $row['id'] ?>, '<?= $row['placa'] ?>')">✏️</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="eliminar_placa" class="btn btn-danger btn-pequeno" onclick="return confirm('¿Eliminar?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="card">
                <h2>🔢 Números Económicos</h2>
                <form method="POST" class="form-inline">
                    <input type="text" name="no_economico" placeholder="Ej: A-01" required>
                    <button type="submit" name="agregar_no_economico" class="btn btn-success">➕ Agregar</button>
                </form>
                <table class="tabla-catalogo">
                    <thead><tr><th>ID</th><th>Número Económico</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php while($row = $catalogos['no_economicos']->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['id'] ?></td>
                            <td id="noe_<?= $row['id'] ?>"><?= htmlspecialchars($row['no_economico']) ?></td>
                            <td><button class="btn btn-warning btn-pequeno" onclick="editarNoEconomico(<?= $row['id'] ?>, '<?= $row['no_economico'] ?>')">✏️</button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                    <button type="submit" name="eliminar_no_economico" class="btn btn-danger btn-pequeno" onclick="return confirm('¿Eliminar?')">🗑️</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card" style="margin-top:20px;">
            <h2>👤 Choferes</h2>
            <form method="POST" class="form-inline">
                <input type="text" name="nombre_chofer" placeholder="Nombre del chofer" required style="flex:2;">
                <input type="text" name="placas_chofer" placeholder="Placas" required style="flex:1;">
                <input type="text" name="numero_economico_chofer" placeholder="Ej: A-01" required style="flex:1;">
                <button type="submit" name="agregar_chofer" class="btn btn-success">➕ Agregar</button>
            </form>
            <table class="tabla-catalogo">
                <thead><tr><th>ID</th><th>Nombre</th><th>Placas</th><th>No. Económico</th><th>Acciones</th></tr></thead>
                <tbody>
                    <?php while($row = $catalogos['choferes']->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['id'] ?></td>
                        <td id="chofer_nombre_<?= $row['id'] ?>"><?= htmlspecialchars($row['nombre_chofer']) ?></td>
                        <td id="chofer_placas_<?= $row['id'] ?>"><?= htmlspecialchars($row['placas']) ?></td>
                        <td id="chofer_noe_<?= $row['id'] ?>"><?= htmlspecialchars($row['numero_economico']) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                <button type="submit" name="eliminar_chofer" class="btn btn-danger btn-pequeno" onclick="return confirm('¿Eliminar este chofer?')">🗑️</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
// ============ FUNCIONES GENERALES ============
function toggleMenu() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('overlay');
    sidebar.classList.toggle('open');
    overlay.classList.toggle('show');
    girarLogoInferior();
}

function girarLogoInferior() {
    const logo = document.getElementById('logoFijo');
    logo.classList.remove('girar-logo');
    void logo.offsetWidth; 
    logo.classList.add('girar-logo');
    setTimeout(() => logo.classList.remove('girar-logo'), 5000);
}

document.addEventListener('DOMContentLoaded', () => girarLogoInferior());

// ============ CONTADOR DE DESTINOS SELECCIONADOS ============
document.querySelectorAll('input[name="destinos[]"]').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const contador = document.querySelectorAll('input[name="destinos[]"]:checked').length;
        document.getElementById('contadorDestinos').textContent = contador;
    });
});

// ============ EXPORTACIONES ============
function exportarExcel() {
    let params = new URLSearchParams(window.location.search);
    window.location.href = 'exportar_excel.php?' + params.toString();
}

function exportarCSV() {
    let params = new URLSearchParams(window.location.search);
    window.location.href = 'exportar_csv.php?' + params.toString();
}

function exportarPDF() {
    let params = new URLSearchParams(window.location.search);
    window.location.href = 'exportar_pdf_tcpdf.php?' + params.toString();
}

// ============ MODALES ============
function cerrarModal() {
    document.getElementById('detalleModal').style.display = 'none';
}

function cerrarSemanaModal() {
    document.getElementById('semanaModal').style.display = 'none';
}

function cerrarImageModal() {
    document.getElementById('imageModal').style.display = 'none';
}

function verImagenGrande(src) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('modalImage');
    modal.style.display = 'flex';
    modalImg.src = src;
}

// ============ DETALLE DE VIAJE ============
function verDetalle(id) {
    const modal = document.getElementById('detalleModal');
    const body = document.getElementById('modalBody');
    girarLogoInferior();
    body.innerHTML = '<span class="cerrar-modal" onclick="cerrarModal()">&times;</span><div style="text-align:center; padding:40px;">Cargando detalles...</div>';
    modal.style.display = 'flex';
    
    fetch('obtener_reporte.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            const totalGastos = parseFloat(data.total_general) || 0;
            let fotosHtml = '<div class="grupo-fotos">';
            const fotos = [
                { label: 'KM Inicial', src: data.foto_inicio },
                { label: 'KM Final', src: data.foto_fin },
                { label: 'Hotel Ida', src: data.foto_hotel_ida },
                { label: 'Hotel Regreso', src: data.foto_hotel_regreso },
                { label: 'Caseta Ida', src: data.foto_caseta_ida },
                { label: 'Caseta Regreso', src: data.foto_caseta_regreso },
                { label: 'Comida Ida', src: data.foto_comida_ida },
                { label: 'Comida Regreso', src: data.foto_comida_regreso },
                { label: 'Estacionamiento Ida', src: data.foto_estac_ida },
                { label: 'Estacionamiento Regreso', src: data.foto_estac_regreso },
                { label: 'Gasolina Ida', src: data.foto_gasolina_ida },
                { label: 'Gasolina Regreso', src: data.foto_gasolina_regreso }
            ];
            fotos.forEach(foto => {
                if (foto.src && foto.src.trim() !== '') {
                    let src = foto.src;
                    if (!src.startsWith('/') && !src.startsWith('http')) {
                        src = '/' + src;
                    }
                    fotosHtml += `<img src="${src}" onclick="verImagenGrande('${src}')" title="${foto.label}">`;
                }
            });
            fotosHtml += '</div>';
            
            const safeFloat = (val) => isNaN(parseFloat(val)) ? 0 : parseFloat(val);
            
            body.innerHTML = `
                <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
                <h2 style="color:#4A148C;">DETALLE DEL VIAJE #${data.id}</h2>
                <div style="background:#f5f5f5; padding:12px; border-radius:10px; margin:10px 0;">
                    <p><strong>Chofer:</strong> ${data.chofer}</p>
                    <p><strong>Vehículo:</strong> ${data.placas} | <strong>No. Económico:</strong> ${data.no_economico || 'N/A'}</p>
                    <p><strong>Ubicación GPS:</strong> ${data.direccion_actual}</p>
                    <p><strong>Fecha:</strong> ${data.fecha}</p>
                    <p><strong>Km inicial:</strong> ${data.km_inicial || 0} | <strong>Km final:</strong> ${data.km_final || 0} | <strong>Km recorrido:</strong> ${data.km_total || 0}</p>
                </div>
                <div class="grid-2col">
                    <div class="seccion-viaje">
                        <h3>TRAYECTO IDA</h3>
                        <p>Origen: ${data.origen_ida}</p>
                        <p>Destino: ${data.destino_ida}</p>
                        <p>Hotel: $${safeFloat(data.gasto_hotel_ida).toFixed(2)}</p>
                        <p>Caseta: $${safeFloat(data.gasto_caseta_ida).toFixed(2)}</p>
                        <p>Comida: $${safeFloat(data.gasto_comida_ida).toFixed(2)}</p>
                        <p>Estacionamiento: $${safeFloat(data.gasto_estac_ida).toFixed(2)}</p>
                        <p><strong>Gasolina: $${safeFloat(data.gasto_gasolina_ida).toFixed(2)}</strong></p>
                    </div>
                    <div class="seccion-viaje seccion-viaje-regreso">
                        <h3>TRAYECTO REGRESO</h3>
                        <p>Origen: ${data.origen_regreso}</p>
                        <p>Destino: ${data.destino_regreso}</p>
                        <p>Hotel: $${safeFloat(data.gasto_hotel_reg).toFixed(2)}</p>
                        <p>Caseta: $${safeFloat(data.gasto_caseta_reg).toFixed(2)}</p>
                        <p>Comida: $${safeFloat(data.gasto_comida_reg).toFixed(2)}</p>
                        <p>Estacionamiento: $${safeFloat(data.gasto_estac_reg).toFixed(2)}</p>
                        <p><strong>Gasolina: $${safeFloat(data.gasto_gasolina_reg).toFixed(2)}</strong></p>
                    </div>
                </div>
                <div style="background:#E1BEE7; padding:15px; border-radius:10px; text-align:center;">
                    <p style="font-size:18px;"><strong>TOTAL DE GASTOS: $${totalGastos.toFixed(2)}</strong></p>
                </div>
                <h3 style="margin-top:15px; text-align:center;">Evidencias Fotográficas</h3>
                ${fotosHtml}
            `;
        })
        .catch(err => {
            body.innerHTML = `<span class="cerrar-modal" onclick="cerrarModal()">&times;</span><div style="text-align:center; padding:40px; color:red;"><h3>Error al cargar los detalles</h3><button class="btn btn-primary" onclick="cerrarModal()">Cerrar</button></div>`;
        });
}

// ============ ELIMINAR VIAJE ============
function eliminarViaje(id) {
    if (!confirm('¿Estás seguro de eliminar este viaje? Se eliminarán también todas las fotos asociadas.')) {
        return;
    }
    fetch('eliminar_viaje.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            alert(data.mensaje);
            if (data.success) {
                location.reload();
            }
        })
        .catch(error => {
            alert('Error al eliminar: ' + error);
        });
}

// ============ DETALLE RUTA ============
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
            let paradasHtml = paradas.length === 0 ? '<p>No hay paradas</p>' : 
                '<ul style="list-style:none; padding:0;">' + paradas.map(p => {
                    const destino = p.razon_social ? p.razon_social + ' - ' + p.sucursal : p.destino_manual || 'Manual';
                    return `<li style="padding:6px 0; border-bottom:1px solid #eee;">#${p.orden}: <strong>${destino}</strong> - Gastos: $${(parseFloat(p.gasto_hotel||0)+parseFloat(p.gasto_caseta||0)+parseFloat(p.gasto_comida||0)+parseFloat(p.gasto_estacionamiento||0)+parseFloat(p.gasto_gasolina||0)).toFixed(2)}</li>`;
                }).join('') + '</ul>';
            
            body.innerHTML = `
                <span class="cerrar-modal" onclick="cerrarModal()">&times;</span>
                <h2 style="color:#4A148C;">📋 Detalle de Ruta #${r.id}</h2>
                <div style="background:#f5f5f5; padding:15px; border-radius:10px; margin:10px 0;">
                    <p><strong>Chofer:</strong> ${r.chofer}</p>
                    <p><strong>Vehículo:</strong> ${r.placas} | ${r.no_economico}</p>
                    <p><strong>Origen:</strong> ${r.origen}</p>
                    <p><strong>Destino Final:</strong> ${r.destino_final || 'Pendiente'}</p>
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

// ============ EDICIÓN DE CATÁLOGOS ============
function editarPlaca(id, actual) {
    let nueva = prompt("Editar placa:", actual);
    if (nueva && nueva !== actual) {
        let f = document.createElement('form');
        f.method = 'POST';
        f.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="placa" value="${nueva}"><input type="hidden" name="editar_placa" value="1">`;
        document.body.appendChild(f);
        f.submit();
    }
}

function editarNoEconomico(id, actual) {
    let nueva = prompt("Editar número económico:", actual);
    if (nueva && nueva !== actual) {
        let f = document.createElement('form');
        f.method = 'POST';
        f.innerHTML = `<input type="hidden" name="id" value="${id}"><input type="hidden" name="no_economico" value="${nueva}"><input type="hidden" name="editar_no_economico" value="1">`;
        document.body.appendChild(f);
        f.submit();
    }
}

// ============ EDITAR DESTINO ============
function editarDestino(id) {
    document.getElementById('edit_destino_id').value = id;
    document.getElementById('edit_razon_social').value = document.getElementById('ds_razon_' + id).innerText;
    document.getElementById('edit_sucursal').value = document.getElementById('ds_sucursal_' + id).innerText;
    document.getElementById('edit_direccion').value = document.getElementById('ds_direccion_' + id).innerText;
    document.getElementById('editarDestinoForm').style.display = 'block';
    document.getElementById('editarDestinoForm').scrollIntoView({ behavior: 'smooth' });
}

function cancelarEditarDestino() {
    document.getElementById('editarDestinoForm').style.display = 'none';
}

// ============ DETALLE SEMANA ============
document.getElementById('btnDetalleSemana')?.addEventListener('click', function() {
    const semanaSelect = document.getElementById('semanaSelect');
    const semanaValor = semanaSelect.value;
    if (!semanaValor) {
        alert('Por favor, selecciona una semana para ver el detalle');
        return;
    }
    
    const modal = document.getElementById('semanaModal');
    const content = document.getElementById('semanaDetalleContent');
    modal.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:40px;">Cargando detalles de la semana...</div>';
    
    fetch('?accion=get_semana_data&semana=' + semanaValor)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div style="margin-bottom:20px; background:#f5f5f5; padding:15px; border-radius:10px;">
                    <p><strong>Semana:</strong> ${data.semana}</p>
                    <p><strong>Período:</strong> ${data.fecha_inicio} al ${data.fecha_fin}</p>
                    <p><strong>Total de viajes:</strong> ${data.viajes.length}</p>
                </div>
            `;
            
            if (data.viajes.length > 0) {
                html += '<div style="overflow-x: auto;">';
                html += '<table class="tabla-semana" style="width:100%; border-collapse:collapse;">';
                html += '<thead><tr style="background:#4A148C; color:white;">';
                html += '<th>ID</th><th>Fecha</th><th>Chofer</th><th>Placas</th><th>Destino</th>';
                html += '<th>Km Inicial</th><th>Km Final</th><th>Km Total</th><th>Total Gastos</th>';
                html += '</tr></thead><tbody>';
                
                data.viajes.forEach(v => {
                    html += `<tr>
                        <td style="white-space:nowrap;">${v.id}</td>
                        <td style="white-space:nowrap;">${v.fecha}</td>
                        <td style="white-space:nowrap;">${v.chofer}</td>
                        <td style="white-space:nowrap;">${v.placas}</td>
                        <td style="white-space:nowrap;">${v.destino}</td>
                        <td style="white-space:nowrap; text-align:center;">${v.km_inicial}</td>
                        <td style="white-space:nowrap; text-align:center;">${v.km_final}</td>
                        <td style="white-space:nowrap; text-align:center;">${v.km_total}</td>
                        <td style="white-space:nowrap; text-align:right;">$${v.gasto}</td>
                    </tr>`;
                });
                html += '</tbody></table></div>';
            } else {
                html += '<p>No hay viajes en esta semana</p>';
            }
            
            html += `
                <div style="margin-top:20px; background:#E1BEE7; padding:15px; border-radius:10px; text-align:right;">
                    <p style="font-size:18px; color:#4A148C;"><strong>TOTAL DE GASTOS: $${data.total_gastos_semana}</strong></p>
                </div>
            `;
            content.innerHTML = html;
        })
        .catch(err => {
            content.innerHTML = '<div style="text-align:center; padding:40px; color:red;">Error al cargar los detalles de la semana</div>';
        });
});
</script>
</body>
</html>

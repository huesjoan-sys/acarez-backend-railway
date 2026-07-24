<?php
$conn = new mysqli('localhost', 'root', '', 'acarez_logistica');

// Obtener filtros
$semana = $_GET['semana'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

// Construir consulta con filtros
$where = "";
if (!empty($semana)) {
    $year = substr($semana, 0, 4);
    $week = substr($semana, 6);
    $fecha_inicio = date('Y-m-d', strtotime($year . 'W' . $week . '1'));
    $fecha_fin = date('Y-m-d', strtotime($year . 'W' . $week . '7'));
    $where = "WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $where = "WHERE DATE(fecha) BETWEEN '$fecha_inicio' AND '$fecha_fin'";
}

$sql = "SELECT * FROM viajes $where ORDER BY id DESC";
$result = $conn->query($sql);

// Obtener semanas disponibles
$semanas_disponibles = $conn->query("
    SELECT DISTINCT CONCAT(YEAR(fecha), '-W', LPAD(WEEK(fecha, 1), 2, '0')) as semana,
           MIN(DATE(fecha)) as inicio,
           MAX(DATE(fecha)) as fin
    FROM viajes 
    GROUP BY semana 
    ORDER BY semana DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel ACAREZ</title>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Arial; background: #e9ecef; margin: 0; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #0D47A1; border-left: 5px solid #0D47A1; padding-left: 15px; display: inline-block; }
        
        .barra-filtros {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .grupo-filtro {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .grupo-filtro label {
            font-size: 12px;
            font-weight: bold;
            color: #555;
        }
        .grupo-filtro select, .grupo-filtro input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-filtrar { background: #0D47A1; color: white; }
        .btn-excel { background: #28a745; color: white; }
        .btn-limpiar { background: #6c757d; color: white; }
        
        .barra-botones {
            background: white;
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .viaje-card {
            background: white;
            border-radius: 12px;
            margin-bottom: 25px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .header-viaje {
            border-bottom: 2px solid #0D47A1;
            padding-bottom: 10px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            align-items: center;
        }
        .chofer-info { font-size: 18px; font-weight: bold; color: #0D47A1; }
        .btn-factura-item {
            background: #ff9800;
            color: white;
            border: none;
            padding: 5px 12px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .seccion { background: #f8f9fa; border-radius: 10px; padding: 15px; }
        .seccion-ida h3 { background: #2196F3; color: white; padding: 8px; border-radius: 8px; text-align: center; margin-top: 0; }
        .seccion-regreso h3 { background: #4CAF50; color: white; padding: 8px; border-radius: 8px; text-align: center; margin-top: 0; }
        .tabla-gastos { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .tabla-gastos td { padding: 8px; border-bottom: 1px solid #ddd; }
        .foto-mini { max-width: 50px; max-height: 50px; border-radius: 6px; cursor: pointer; border: 1px solid #ddd; }
        .totales { background: #e3f2fd; padding: 12px; border-radius: 8px; text-align: center; font-weight: bold; margin-top: 15px; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.9); cursor: pointer; }
        .modal-content { margin: auto; display: block; max-width: 90%; max-height: 90%; margin-top: 50px; }
        .close { position: absolute; top: 15px; right: 35px; color: white; font-size: 40px; cursor: pointer; }
        .resumen-filtro {
            background: #e3f2fd;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        @media (max-width: 768px) { .grid-2col { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<div class="container">
    <h1>🚚 ACAREZ LOGÍSTICA - Reportes de Viajes</h1>
    
    <!-- FILTROS -->
    <form method="GET" class="barra-filtros">
        <div class="grupo-filtro">
            <label>📅 Seleccionar Semana</label>
            <select name="semana">
                <option value="">-- Todas las semanas --</option>
                <?php while($row = $semanas_disponibles->fetch_assoc()): ?>
                    <option value="<?= $row['semana'] ?>" <?= ($semana == $row['semana']) ? 'selected' : '' ?>>
                        Semana <?= substr($row['semana'], -2) ?> (<?= $row['inicio'] ?> al <?= $row['fin'] ?>)
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="grupo-filtro">
            <label>📆 O rango de fechas</label>
            <div style="display: flex; gap: 8px;">
                <input type="date" name="fecha_inicio" value="<?= $fecha_inicio ?>" placeholder="Desde">
                <span>a</span>
                <input type="date" name="fecha_fin" value="<?= $fecha_fin ?>" placeholder="Hasta">
            </div>
        </div>
        <div class="grupo-filtro">
            <button type="submit" class="btn btn-filtrar">🔍 Filtrar</button>
        </div>
        <div class="grupo-filtro">
            <a href="panel.php" class="btn btn-limpiar" style="text-decoration: none;">🗑️ Limpiar filtros</a>
        </div>
    </form>
    
    <!-- BOTONES -->
    <div class="barra-botones">
        <button class="btn btn-excel" onclick="exportarExcel()">📊 Exportar a Excel</button>
        <button class="btn btn-excel" onclick="exportarExcelTodo()">📊 Exportar TODO</button>
        <button class="btn btn-filtrar" onclick="window.location.reload()">🔄 Actualizar</button>
    </div>
    
    <?php if($result->num_rows == 0): ?>
        <div style="background: white; padding: 40px; text-align: center; border-radius: 12px;">
            📭 No hay reportes para los filtros seleccionados.
        </div>
    <?php endif; ?>
    
    <?php while($row = $result->fetch_assoc()): ?>
    <div class="viaje-card">
        <div class="header-viaje">
            <div>
                <span class="chofer-info">👨‍✈️ <?= htmlspecialchars($row['chofer']) ?></span>
                <span style="margin-left: 15px;">🚛 <?= htmlspecialchars($row['placas']) ?></span>
                <span style="margin-left: 15px; background: #e3f2fd; padding: 4px 10px; border-radius: 20px; font-size: 13px;">
                    🔢 No. Económico: <strong><?= htmlspecialchars($row['no_economico']) ?></strong>
                </span>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="fecha-info">📅 <?= date('d/m/Y H:i', strtotime($row['fecha'])) ?></span>
                <button class="btn-factura-item" onclick="generarFactura(<?= $row['id'] ?>)">🧾 Facturar este viaje</button>
            </div>
        </div>
        
        <div style="background:#fff3e0; padding:10px; border-radius:8px; margin-bottom:20px;">
            📍 <strong>Ubicación al enviar:</strong> <?= htmlspecialchars($row['direccion_actual']) ?>
        </div>
        
        <div class="grid-2col">
            <!-- IDA -->
            <div class="seccion seccion-ida">
                <h3>📍 TRAYECTO DE IDA</h3>
                <p><strong>Origen:</strong> <?= htmlspecialchars($row['origen_ida']) ?></p>
                <p><strong>Destino:</strong> <?= htmlspecialchars($row['destino_ida']) ?></p>
                <table class="tabla-gastos">
                    <tr><td>🏨 Hotel</td><td>$<?= number_format($row['gasto_hotel_ida'], 2) ?></td>
                        <td><?php if($row['foto_hotel_ida']): ?><img src="<?= $row['foto_hotel_ida'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_hotel_ida'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🛣️ Caseta</td><td>$<?= number_format($row['gasto_caseta_ida'], 2) ?></td>
                        <td><?php if($row['foto_caseta_ida']): ?><img src="<?= $row['foto_caseta_ida'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_caseta_ida'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🍽️ Comida</td><td>$<?= number_format($row['gasto_comida_ida'], 2) ?></td>
                        <td><?php if($row['foto_comida_ida']): ?><img src="<?= $row['foto_comida_ida'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_comida_ida'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🅿️ Estacionamiento</td><td>$<?= number_format($row['gasto_estac_ida'], 2) ?></td>
                        <td><?php if($row['foto_estac_ida']): ?><img src="<?= $row['foto_estac_ida'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_estac_ida'] ?>')"><?php endif; ?></td></tr>
                </table>
                <div class="totales">💰 Total Ida: $<?= number_format($row['total_ida'], 2) ?></div>
            </div>
            
            <!-- REGRESO -->
            <div class="seccion seccion-regreso">
                <h3>🔄 TRAYECTO DE REGRESO</h3>
                <p><strong>Origen:</strong> <?= htmlspecialchars($row['origen_regreso']) ?></p>
                <p><strong>Destino:</strong> <?= htmlspecialchars($row['destino_regreso']) ?></p>
                <table class="tabla-gastos">
                    <tr><td>🏨 Hotel</td><td>$<?= number_format($row['gasto_hotel_reg'], 2) ?></td>
                        <td><?php if($row['foto_hotel_regreso']): ?><img src="<?= $row['foto_hotel_regreso'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_hotel_regreso'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🛣️ Caseta</td><td>$<?= number_format($row['gasto_caseta_reg'], 2) ?></td>
                        <td><?php if($row['foto_caseta_regreso']): ?><img src="<?= $row['foto_caseta_regreso'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_caseta_regreso'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🍽️ Comida</td><td>$<?= number_format($row['gasto_comida_reg'], 2) ?></td>
                        <td><?php if($row['foto_comida_regreso']): ?><img src="<?= $row['foto_comida_regreso'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_comida_regreso'] ?>')"><?php endif; ?></td></tr>
                    <tr><td>🅿️ Estacionamiento</td><td>$<?= number_format($row['gasto_estac_reg'], 2) ?></td>
                        <td><?php if($row['foto_estac_regreso']): ?><img src="<?= $row['foto_estac_regreso'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_estac_regreso'] ?>')"><?php endif; ?></td></tr>
                </table>
                <div class="totales">💰 Total Regreso: $<?= number_format($row['total_regreso'], 2) ?></div>
            </div>
        </div>
        
        <!-- KILOMETRAJE -->
        <div style="background:#e8eaf6; padding:15px; border-radius:10px; margin-top:10px;">
            <h4 style="margin:0 0 10px 0;">📸 KILOMETRAJE</h4>
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div><strong>KM Inicial:</strong><br>
                    <?php if($row['foto_inicio']): ?>
                        <img src="<?= $row['foto_inicio'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_inicio'] ?>')" style="max-width:100px; max-height:100px;">
                    <?php else: ?> <span style="color:#999;">Sin foto</span> <?php endif; ?>
                </div>
                <div><strong>KM Final:</strong><br>
                    <?php if($row['foto_fin']): ?>
                        <img src="<?= $row['foto_fin'] ?>" class="foto-mini" onclick="verImagen('<?= $row['foto_fin'] ?>')" style="max-width:100px; max-height:100px;">
                    <?php else: ?> <span style="color:#999;">Sin foto</span> <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div style="background:#0D47A1; color:white; padding:12px; border-radius:8px; text-align:center; margin-top:15px; font-size:18px;">
            🧾 TOTAL DEL VIAJE: $<?= number_format($row['total_general'], 2) ?>
        </div>
    </div>
    <?php endwhile; ?>
</div>

<!-- Modal para imagen -->
<div id="imageModal" class="modal" onclick="cerrarModal()">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
function verImagen(src) {
    document.getElementById('imageModal').style.display = 'block';
    document.getElementById('modalImage').src = src;
}
function cerrarModal() { document.getElementById('imageModal').style.display = 'none'; }
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') cerrarModal(); });

function exportarExcel() {
    let params = new URLSearchParams(window.location.search);
    window.location.href = 'exportar_excel.php?' + params.toString();
}
function exportarExcelTodo() {
    window.location.href = 'exportar_excel.php?todo=1';
}
function generarFactura(id) {
    window.open('generar_factura.php?id=' + id, '_blank');
}
</script>
</body>
</html>

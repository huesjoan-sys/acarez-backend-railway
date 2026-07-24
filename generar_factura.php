<?php
$id = $_GET['id'] ?? 0;
$conn = new mysqli('localhost', 'root', '', 'acarez_logistica');
$result = $conn->query("SELECT * FROM viajes WHERE id = $id");
$row = $result->fetch_assoc();

if(!$row) {
    die("Viaje no encontrado");
}

$fecha_completa = $row['fecha'];
$fecha = date('d/m/Y', strtotime($fecha_completa));
$hora = date('H:i:s', strtotime($fecha_completa));
$num_factura = str_pad($row['id'], 8, '0', STR_PAD_LEFT);
$subtotal = $row['total_general'];
$iva = $subtotal * 0.16;
$total_con_iva = $subtotal + $iva;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Factura ACAREZ #<?= $num_factura ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: #e9ecef;
            padding: 40px;
        }
        .factura-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        /* ENCABEZADO BLANCO CON TEXTO MORADO */
        .header-corporativo {
            background: white;
            color: #4A148C;
            padding: 30px;
            text-align: center;
            border-bottom: 2px solid #4A148C;
        }
        .logo-contenedor {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .logo-img {
            width: 70px;
            height: auto;
            /* Sin filtros: colores originales */
        }
        .logo-texto {
            text-align: left;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            color: #4A148C;
        }
        .logo span {
            font-size: 12px;
            display: block;
            opacity: 0.8;
            color: #6A1B9A;
        }
        .titulo-factura {
            font-size: 24px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #4A148C;
            color: #4A148C;
        }
        
        /* Información de la empresa */
        .info-empresa {
            background: #f8f9fa;
            padding: 20px 30px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .info-item {
            font-size: 13px;
            color: #555;
        }
        .info-item strong {
            color: #4A148C;
        }
        
        /* Datos del cliente / viaje */
        .datos-viaje {
            padding: 25px 30px;
            background: white;
            border-bottom: 1px solid #eee;
        }
        .datos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }
        .dato {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border-left: 4px solid #4A148C;
        }
        .dato-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #888;
            letter-spacing: 1px;
        }
        .dato-valor {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            margin-top: 4px;
        }
        
        /* Tabla de servicios */
        .tabla-servicios {
            padding: 0 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th {
            background: #4A148C;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
        }
        td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 13px;
        }
        .subtitulo-tabla {
            background: #f0f4f8;
            font-weight: bold;
        }
        .total-final {
            text-align: right;
            padding: 20px 30px;
            background: #f8f9fa;
            margin-top: 10px;
        }
        .total-line {
            padding: 8px 0;
        }
        .total-line strong {
            font-size: 18px;
            color: #4A148C;
        }
        .total-grande {
            font-size: 24px;
            font-weight: bold;
            color: #4A148C;
        }
        
        /* Footer MORADO */
        .footer {
            background: #4A148C;
            color: white;
            padding: 20px 30px;
            text-align: center;
            font-size: 11px;
            margin-top: 20px;
        }
        
        /* Botones */
        .botones-accion {
            text-align: center;
            padding: 20px;
            background: white;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 10px 25px;
            margin: 0 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.2s;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-imprimir { background: #4A148C; color: white; }
        .btn-cerrar { background: #6c757d; color: white; }
        
        @media print {
            body { background: white; padding: 0; }
            .botones-accion { display: none; }
            .factura-container { box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
<div class="factura-container">
    
    <!-- HEADER BLANCO CON LOGO Y TEXTO MORADO -->
    <div class="header-corporativo">
        <div class="logo-contenedor">
            <div>
                <img src="imagenes/acarez_2.png" alt="Logo ACAREZ" class="logo-img">
            </div>
            <div class="logo-texto">
                <div class="logo">
                    ACAREZ LOGÍSTICA
                    <span>Transporte y Mensajería Especializada</span>
                </div>
            </div>
        </div>
        <div class="titulo-factura">
            FACTURA ELECTRÓNICA
        </div>
    </div>
    
    <!-- INFO EMPRESA -->
    <div class="info-empresa">
        <div class="info-item"><strong>RFC:</strong> ACZ-240410-XXX</div>
        <div class="info-item"><strong>Teléfono:</strong> (55) 1234-5678</div>
        <div class="info-item"><strong>Email:</strong> facturacion@acarez.com</div>
        <div class="info-item"><strong>Dirección:</strong> Av. Principal #123, CDMX</div>
    </div>
    
    <!-- DATOS DEL VIAJE -->
    <div class="datos-viaje">
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
            <div>
                <strong style="color:#4A148C;">FOLIO FACTURA:</strong> #<?= $num_factura ?>
            </div>
            <div>
                <strong style="color:#4A148C;">FECHA DE EMISIÓN:</strong> <?= $fecha ?> | <?= $hora ?> hrs
            </div>
        </div>
        <div class="datos-grid">
            <div class="dato">
                <div class="dato-label">CHOFER / OPERADOR</div>
                <div class="dato-valor"><?= htmlspecialchars($row['chofer']) ?></div>
            </div>
            <div class="dato">
                <div class="dato-label">UNIDAD / PLACAS</div>
                <div class="dato-valor"><?= htmlspecialchars($row['placas']) ?></div>
            </div>
            <div class="dato">
                <div class="dato-label">UBICACIÓN GPS</div>
                <div class="dato-valor"><?= htmlspecialchars($row['direccion_actual']) ?></div>
            </div>
        </div>
    </div>
    
    <!-- TABLA DE SERVICIOS -->
    <div class="tabla-servicios">
        <h4 style="margin: 15px 0 0 0; color:#4A148C;">📋 DETALLE DE SERVICIOS</h4>
        <table>
            <thead>
                <tr>
                    <th>CONCEPTO</th>
                    <th>IDA</th>
                    <th>REGRESO</th>
                    <th>TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <tr class="subtitulo-tabla">
                    <td colspan="4"><strong>📍 TRAYECTOS</strong></td>
                </tr>
                <tr>
                    <td>Origen / Destino
                    <td><?= htmlspecialchars($row['origen_ida']) ?> → <?= htmlspecialchars($row['destino_ida']) ?>
                    <td><?= htmlspecialchars($row['origen_regreso']) ?> → <?= htmlspecialchars($row['destino_regreso']) ?>
                    <td>-
                </tr>
                <tr class="subtitulo-tabla">
                    <td colspan="4"><strong>💰 GASTOS DE OPERACIÓN</strong></td>
                </tr>
                <tr>
                    <td>🏨 Hotel / Hospedaje
                    <td>$<?= number_format($row['gasto_hotel_ida'], 2) ?>
                    <td>$<?= number_format($row['gasto_hotel_reg'], 2) ?>
                    <td>$<?= number_format($row['gasto_hotel_ida'] + $row['gasto_hotel_reg'], 2) ?>
                </tr>
                <tr>
                    <td>🛣️ Autopistas / Casetas
                    <td>$<?= number_format($row['gasto_caseta_ida'], 2) ?>
                    <td>$<?= number_format($row['gasto_caseta_reg'], 2) ?>
                    <td>$<?= number_format($row['gasto_caseta_ida'] + $row['gasto_caseta_reg'], 2) ?>
                </tr>
                <tr>
                    <td>🍽️ Alimentación / Comidas
                    <td>$<?= number_format($row['gasto_comida_ida'], 2) ?>
                    <td>$<?= number_format($row['gasto_comida_reg'], 2) ?>
                    <td>$<?= number_format($row['gasto_comida_ida'] + $row['gasto_comida_reg'], 2) ?>
                </tr>
                <tr>
                    <td>🅿️ Estacionamiento
                    <td>$<?= number_format($row['gasto_estac_ida'], 2) ?>
                    <td>$<?= number_format($row['gasto_estac_reg'], 2) ?>
                    <td>$<?= number_format($row['gasto_estac_ida'] + $row['gasto_estac_reg'], 2) ?>
                </tr>
                <tr style="background:#f0f4f8; font-weight:bold;">
                    <td>SUBTOTAL POR TRAYECTO
                    <td>$<?= number_format($row['total_ida'], 2) ?>
                    <td>$<?= number_format($row['total_regreso'], 2) ?>
                    <td>$<?= number_format($subtotal, 2) ?>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- TOTALES -->
    <div class="total-final">
        <div class="total-line">Subtotal: $<?= number_format($subtotal, 2) ?></div>
        <div class="total-line">IVA (16%): $<?= number_format($iva, 2) ?></div>
        <div class="total-line"><strong>TOTAL A PAGAR:</strong> <span class="total-grande">$<?= number_format($total_con_iva, 2) ?></span></div>
        <div class="total-line" style="font-size: 11px; color: #888;">* Este documento es una representación impresa de la factura electrónica</div>
    </div>
    
    <!-- FOOTER MORADO -->
    <div class="footer">
        <p>Gracias por preferir ACAREZ LOGÍSTICA</p>
        <p>© <?= date('Y') ?> - Todos los derechos reservados</p>
    </div>
    
    <!-- BOTONES -->
    <div class="botones-accion">
        <button class="btn btn-imprimir" onclick="window.print()">🖨️ IMPRIMIR FACTURA</button>
        <button class="btn btn-cerrar" onclick="window.close()">❌ CERRAR</button>
    </div>
    
</div>
</body>
</html>

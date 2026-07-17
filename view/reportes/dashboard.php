<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
require_once(__DIR__ . "/../../config/conexion.php");

$conexion = new Conexion();
$cn = $conexion->conectar();

// 1. Estadísticas de inventario
// Valor total de inventario (Stock * precio)
$resValuation = $cn->query("SELECT SUM(stock * precio) AS total_valuation, SUM(stock) AS total_items, COUNT(*) AS count_products FROM productos");
$valuation = (new OracleResult($resValuation))->fetch_assoc();

// Cantidad de productos con bajo stock (<= 5)
$resLowStock = $cn->query("SELECT COUNT(*) AS total_low FROM productos WHERE stock <= 5");
$lowStock = (new OracleResult($resLowStock))->fetch_assoc();

// Detalle de productos con bajo stock (Oracle compatible)
$lowStockList = new OracleResult($cn->query("SELECT * FROM (SELECT codigo, nombre, stock, unidadMedida FROM productos WHERE stock <= 5 ORDER BY stock ASC) WHERE ROWNUM <= 10"));

// Ranking de productos más vendidos (Oracle compatible)
$topProducts = new OracleResult($cn->query("
    SELECT * FROM (
        SELECT p.codigo, p.nombre, SUM(dv.cantidad) AS total_vendido, SUM(dv.total) AS total_ventas_usd 
        FROM detalle_ventas dv 
        INNER JOIN productos p ON dv.idProducto = p.idProducto 
        GROUP BY p.codigo, p.nombre 
        ORDER BY total_vendido DESC
    ) WHERE ROWNUM <= 5
"));

// 2. Estadísticas de clientes
// Mejores clientes (ranking de ventas por monto, Oracle compatible)
$topClients = new OracleResult($cn->query("
    SELECT * FROM (
        SELECT c.cedula, c.nombres, c.apellidos, COUNT(v.idVenta) AS total_compras, SUM(v.total) AS total_gastado 
        FROM ventas v
        INNER JOIN clientes c ON v.idCliente = c.idCliente
        GROUP BY c.idCliente, c.cedula, c.nombres, c.apellidos
        ORDER BY total_gastado DESC
    ) WHERE ROWNUM <= 10
"));

// Total de clientes registrados
$resTotalClients = $cn->query("SELECT COUNT(*) as total FROM clientes");
$totalClients = (new OracleResult($resTotalClients))->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/reportes/">
<title>Reportes y Estad&iacute;sticas &middot; Sistema de Inventario</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
<style>
    .gridReportes {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-top: 20px;
    }
    @media (max-width: 900px) {
        .gridReportes {
            grid-template-columns: 1fr;
        }
    }
    .panelReporte {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: 20px;
    }
    .resumenKpi {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    .kpiCard {
        background: rgba(255,255,255,0.02);
        border: 1px solid rgba(255,255,255,0.06);
        border-radius: var(--radius-md);
        padding: 15px;
        text-align: center;
    }
    .kpiCard .valor {
        font-size: 22px;
        font-weight: 800;
        margin: 5px 0;
    }
    .kpiCard.alerta .valor {
        color: #f43f5e;
    }
    .kpiCard.ok .valor {
        color: #10b981;
    }
    .kpiCard .etiqueta {
        font-size: 12px;
        color: rgba(255,255,255,0.6);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Análisis de Datos</p>
        <h2>Reportes del Sistema</h2>
        <p class="descripcionSeccion">Informaci&oacute;n gerencial de valor de inventario, stock bajo y ranking de clientes m&aacute;s destacados.</p>
    </div>
</div>

<!-- Indicadores Clave de Rendimiento (KPIs) -->
<div class="resumenKpi">
    <div class="kpiCard ok">
        <div class="etiqueta">Valor del Inventario</div>
        <div class="valor">$<?php echo number_format($valuation['total_valuation'] ?? 0, 2); ?></div>
    </div>
    <div class="kpiCard">
        <div class="etiqueta">Productos Totales</div>
        <div class="valor"><?php echo (int)$valuation['count_products']; ?></div>
    </div>
    <div class="kpiCard <?php echo $lowStock['total_low'] > 0 ? 'alerta' : ''; ?>">
        <div class="etiqueta">Stock Cr&iacute;tico (<= 5)</div>
        <div class="valor"><?php echo (int)$lowStock['total_low']; ?></div>
    </div>
    <div class="kpiCard ok">
        <div class="etiqueta">Clientes Totales</div>
        <div class="valor"><?php echo (int)$totalClients['total']; ?></div>
    </div>
</div>

<div class="gridReportes">
    <!-- Panel 1: Inventario Crítico y Más Vendidos -->
    <div class="panelReporte">
        <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--color-border); padding-bottom: 8px;">Alertas de Stock Cr&iacute;tico</h3>
        <div class="tablaPanel" style="margin-bottom: 25px;">
            <table>
                <thead>
                    <tr>
                        <th>C&oacute;digo</th>
                        <th>Producto</th>
                        <th>Stock Actual</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($lowStockList->num_rows > 0) { ?>
                        <?php while ($prod = $lowStockList->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prod['codigo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                <td>
                                    <span class="stock-badge" style="background: rgba(244,63,94,0.15); color: #f43f5e; padding: 2px 8px; border-radius: 4px;">
                                        <?php echo number_format($prod['stock'], 2); ?> <?php echo htmlspecialchars($prod['unidadMedida']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: rgba(255,255,255,0.4);">No hay productos con stock cr&iacute;tico. ¡Excelente!</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--color-border); padding-bottom: 8px;">Productos M&aacute;s Vendidos (Volumen)</h3>
        <div class="tablaPanel">
            <table>
                <thead>
                    <tr>
                        <th>C&oacute;digo</th>
                        <th>Producto</th>
                        <th>Cantidad Vendida</th>
                        <th>Total USD</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($topProducts->num_rows > 0) { ?>
                        <?php while ($prod = $topProducts->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($prod['codigo']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['nombre']); ?></td>
                                <td><?php echo number_format($prod['total_vendido'], 2); ?></td>
                                <td><strong>$<?php echo number_format($prod['total_ventas_usd'], 2); ?></strong></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: rgba(255,255,255,0.4);">No hay registros de ventas a&uacute;n.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel 2: Ranking de Clientes Destacados -->
    <div class="panelReporte">
        <h3 style="margin-top: 0; margin-bottom: 15px; border-bottom: 1px solid var(--color-border); padding-bottom: 8px;">Ranking de Clientes Destacados</h3>
        <div class="tablaPanel">
            <table>
                <thead>
                    <tr>
                        <th>Identificaci&oacute;n</th>
                        <th>Nombre</th>
                        <th>Nro. Compras</th>
                        <th>Total Gastado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($topClients->num_rows > 0) { ?>
                        <?php while ($cli = $topClients->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cli['cedula']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cli['nombres'] . ' ' . $cli['apellidos']); ?></td>
                                <td><?php echo (int)$cli['total_compras']; ?> facturas</td>
                                <td><strong style="color: var(--color-primary);">$<?php echo number_format($cli['total_gastado'], 2); ?></strong></td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: rgba(255,255,255,0.4);">No se registran clientes con compras en el sistema.</td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</main>
</body>
</html>

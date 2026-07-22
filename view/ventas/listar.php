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
require_once(__DIR__ . "/../../model/venta.php");
require_once(__DIR__ . "/../../model/producto.php");

$modelo = new Venta();
$productoModelo = new Producto();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$idProducto = isset($_GET["idProducto"]) ? (int)$_GET["idProducto"] : 0;
$fechaInicio = isset($_GET["fechaInicio"]) ? trim($_GET["fechaInicio"]) : "";
$fechaFin = isset($_GET["fechaFin"]) ? trim($_GET["fechaFin"]) : "";

if ($fechaInicio !== "" && $fechaFin !== "") {
    $ventas = $modelo->obtenerVentasPorRangoFechas($fechaInicio . " 00:00:00", $fechaFin . " 23:59:59");
    $totalVentas = $ventas->num_rows;
    $totalPaginas = 1;
} else {
    $totalVentas = $modelo->contarTotal($idProducto);
    $totalPaginas = ceil($totalVentas / $limite);
    $offset = ($paginaActual - 1) * $limite;
    $ventas = $modelo->obtenerTodosPaginado($offset, $limite, $idProducto);
}
$todosProductos = $productoModelo->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/ventas/">
<title>Historial de Ventas</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
<style>
    .barraAcciones {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        gap: 15px;
    }
    .filtro {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .filtro select {
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: white;
        border-radius: var(--radius-md);
        font-family: inherit;
    }
</style>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Historial</p>
        <h2>Ventas Registradas</h2>
        <p class="descripcionSeccion">Listado de facturas emitidas e historial de ventas. Filtra por producto específico.</p>
    </div>
</div>

<div class="barraAcciones" style="flex-wrap: wrap; gap: 15px;">
    <form method="GET" action="<?php echo urlLimpia('view/ventas/listar.php'); ?>" class="filtro">
        <label for="idProducto">Filtrar por Producto:</label>
        <select name="idProducto" id="idProducto" onchange="this.form.submit()">
            <option value="0">Todos los productos</option>
            <?php while ($prod = $todosProductos->fetch_assoc()) { ?>
                <option value="<?php echo (int)$prod['idProducto']; ?>" <?php echo $idProducto == $prod['idProducto'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prod['codigo'] . ' - ' . $prod['nombre']); ?>
                </option>
            <?php } ?>
        </select>
    </form>
    
    <form method="GET" action="<?php echo urlLimpia('view/ventas/listar.php'); ?>" class="filtro" style="margin-left: auto; flex-wrap: wrap; gap: 8px;">
        <label for="fechaInicio">Rango Fechas:</label>
        <input type="date" name="fechaInicio" id="fechaInicio" value="<?php echo htmlspecialchars($fechaInicio); ?>" style="padding: 8px 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); color: white; border-radius: var(--radius-md);">
        <span>a</span>
        <input type="date" name="fechaFin" id="fechaFin" value="<?php echo htmlspecialchars($fechaFin); ?>" style="padding: 8px 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.15); color: white; border-radius: var(--radius-md);">
        <button type="submit" class="botonNuevo" style="padding: 8px 16px; font-size: 13px;">Filtrar</button>
        <?php if ($fechaInicio !== "" || $fechaFin !== "") { ?>
            <a href="<?php echo urlLimpia('view/ventas/listar.php'); ?>" class="botonNuevo" style="background: rgba(255,0,0,0.2); border: 1px solid rgba(255,0,0,0.3); color: white; text-decoration: none; padding: 8px 16px; font-size: 13px; display: inline-flex; align-items: center; height: 35px; box-sizing: border-box;">Limpiar</a>
        <?php } ?>
    </form>
</div>

<div class="tablaPanel">
<table>
<thead>
<tr>
<?php if ($idProducto > 0) { ?>
    <th>Fecha / Hora</th>
    <th>Factura Nro.</th>
    <th>Cliente</th>
    <th>Producto</th>
    <th>Cant. Vendida</th>
    <th>Precio Venta</th>
    <th>IVA Item</th>
    <th>Total Item</th>
    <th>Vendedor</th>
<?php } else { ?>
    <th>Factura Nro.</th>
    <th>Fecha / Hora</th>
    <th>Cliente</th>
    <th>Subtotal</th>
    <th>IVA Total</th>
    <th>Total General</th>
    <th>Vendedor</th>
    <th>Acciones</th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php if ($ventas->num_rows > 0) { ?>
<?php while ($fila = $ventas->fetch_assoc()) { ?>
<tr>
<?php if ($idProducto > 0) { ?>
    <td data-label="Fecha"><?php $ts = strtotime($fila["fecha"]); echo ($ts && $ts > 0) ? date("d/m/Y H:i:s", $ts) : htmlspecialchars($fila["fecha"]); ?></td>
    <td data-label="Factura"><strong><?php echo htmlspecialchars($fila["numeroFactura"]); ?></strong></td>
    <td data-label="Cliente">
        <?php 
        if (isset($fila["cliente_nombre"])) {
            echo htmlspecialchars($fila["cliente_nombre"]);
        } else {
            echo htmlspecialchars(($fila["cliente_nombres"] ?? '') . ' ' . ($fila["cliente_apellidos"] ?? ''));
        }
        ?>
    </td>
    <td data-label="Producto"><?php echo htmlspecialchars($fila["producto_nombre"]); ?></td>
    <td data-label="Cantidad"><?php echo number_format($fila["cantidad"], 2); ?></td>
    <td data-label="Precio">$<?php echo number_format($fila["precio_unitario"], 4); ?></td>
    <td data-label="IVA">$<?php echo number_format($fila["iva_item"], 2); ?></td>
    <td data-label="Total"><strong>$<?php echo number_format($fila["total_item"], 2); ?></strong></td>
    <td data-label="Vendedor"><?php echo htmlspecialchars($fila["vendedor"]); ?></td>
<?php } else { ?>
    <td data-label="Factura"><strong><?php echo htmlspecialchars($fila["numeroFactura"]); ?></strong></td>
    <td data-label="Fecha"><?php $ts = strtotime($fila["fecha"]); echo ($ts && $ts > 0) ? date("d/m/Y H:i:s", $ts) : htmlspecialchars($fila["fecha"]); ?></td>
    <td data-label="Cliente">
        <?php 
        if (isset($fila["cliente_nombre"])) {
            echo htmlspecialchars($fila["cliente_nombre"]);
        } else {
            echo htmlspecialchars(($fila["cliente_nombres"] ?? '') . ' ' . ($fila["cliente_apellidos"] ?? ''));
        }
        ?>
    </td>
    <td data-label="Subtotal">$<?php echo number_format($fila["subtotal"], 2); ?></td>
    <td data-label="IVA">$<?php echo number_format($fila["iva"], 2); ?></td>
    <td data-label="Total"><strong>$<?php echo number_format($fila["total"], 2); ?></strong></td>
    <td data-label="Vendedor"><?php echo htmlspecialchars($fila["vendedor"]); ?></td>
    <td data-label="Acciones">
        <div style="display: flex; gap: 5px; flex-wrap: wrap;">
            <button class="accion accionPrimaria" onclick="verDetalle(<?php echo (int)$fila['idVenta']; ?>)">Detalles</button>
            <a href="../../controller/ventaController.php?accion=descargar_pdf&id=<?php echo (int)$fila['idVenta']; ?>" target="_blank" class="accion" style="background-color: #dc2626; color: white; padding: 8px 12px; border-radius: var(--radius-md); text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;">PDF</a>
            <a href="../../controller/ventaController.php?accion=descargar_xml&id=<?php echo (int)$fila['idVenta']; ?>" download class="accion" style="background-color: #2563eb; color: white; padding: 8px 12px; border-radius: var(--radius-md); text-decoration: none; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s;">XML</a>
        </div>
    </td>
<?php } ?>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="<?php echo $idProducto > 0 ? 9 : 8; ?>">
    <div class="estadoVacio">
        No se registraron ventas.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<?php if ($totalPaginas > 1) { ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/ventas/listar.php?pag=" . ($paginaActual - 1) . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
        <a href="<?php echo urlLimpia("view/ventas/listar.php?pag=" . $i . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/ventas/listar.php?pag=" . ($paginaActual + 1) . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>

<div class="modal-overlay" id="modalDetalle">
    <div class="modal-container" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Factura Nro: <span id="lblNumeroFactura"></span></h3>
            <button class="modal-close" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div class="formulario" style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 15px; margin-bottom: 20px; color: rgba(255,255,255,0.8); font-size: 13px;">
                <div>
                    <h4 style="margin: 0 0 8px 0; color: white;">Información del Cliente</h4>
                    <p style="margin: 0 0 4px 0;"><strong>Nombre:</strong> <span id="detCliNombre"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>Cédula / RUC:</strong> <span id="detCliCedula"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>Teléfono:</strong> <span id="detCliTelefono"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>Dirección:</strong> <span id="detCliDireccion"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>Correo:</strong> <span id="detCliCorreo"></span></p>
                </div>
                <div style="text-align: right; border-left: 1px solid var(--color-border); padding-left: 15px;">
                    <h4 style="margin: 0 0 8px 0; color: white; text-align: right;">Resumen Factura</h4>
                    <p style="margin: 0 0 4px 0;"><strong>Vendedor:</strong> <span id="detVendedor"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>Fecha de Emisión:</strong> <span id="detFecha"></span></p>
                    <p style="margin: 10px 0 4px 0;"><strong>Subtotal:</strong> <span id="detSubtotal"></span></p>
                    <p style="margin: 0 0 4px 0;"><strong>IVA total:</strong> <span id="detIva"></span></p>
                    <p style="margin: 0;"><strong>Total a pagar:</strong> <span id="detTotal" style="color: var(--color-primary); font-weight: 800; font-size: 18px;"></span></p>
                </div>
            </div>
            
            <h4 style="margin-bottom: 10px; color: white;">Items Facturados</h4>
            <div class="tablaPanel">
                <table style="width: 100%;">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Unidad</th>
                            <th>Cant.</th>
                            <th>Precio U.</th>
                            <th>IVA</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody id="detProductosCuerpo">
                    </tbody>
                </table>
            </div>
        </div>
        <div class="accionesFormulario" style="padding: 10px 20px 20px 20px; display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
            <a id="btnModalPDF" href="#" target="_blank" class="accion" style="background-color: #dc2626; color: white; padding: 10px 18px; border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box;">Descargar PDF</a>
            <a id="btnModalXML" href="#" download class="accion" style="background-color: #2563eb; color: white; padding: 10px 18px; border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; height: 38px; box-sizing: border-box;">Descargar XML SRI</a>
            <button type="button" class="botonSecundario" onclick="cerrarModalDetalle()" style="height: 38px; display: inline-flex; align-items: center; justify-content: center; box-sizing: border-box; margin: 0;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    const modalDetalle = document.getElementById("modalDetalle");
    
    async function verDetalle(idVenta) {
        try {
            document.getElementById("btnModalPDF").href = `../../controller/ventaController.php?accion=descargar_pdf&id=${idVenta}`;
            document.getElementById("btnModalXML").href = `../../controller/ventaController.php?accion=descargar_xml&id=${idVenta}`;
            
            const res = await fetch(`../../controller/ventaController.php?accion=detalle&id=${idVenta}`);
            const data = await res.json();
            
            if (data.success) {
                const v = data.venta;
                document.getElementById("lblNumeroFactura").textContent = v.numeroFactura;
                document.getElementById("detCliNombre").textContent = `${v.c_nombres} ${v.c_apellidos}`;
                document.getElementById("detCliCedula").textContent = v.c_cedula;
                document.getElementById("detCliTelefono").textContent = v.c_telefono || '-';
                document.getElementById("detCliDireccion").textContent = v.c_direccion || '-';
                document.getElementById("detCliCorreo").textContent = v.c_correo || '-';
                
                document.getElementById("detVendedor").textContent = v.vendedor;
                document.getElementById("detFecha").textContent = new Date(v.fecha).toLocaleString("es-EC");
                document.getElementById("detSubtotal").textContent = "$" + parseFloat(v.subtotal).toFixed(2);
                document.getElementById("detIva").textContent = "$" + parseFloat(v.iva).toFixed(2);
                document.getElementById("detTotal").textContent = "$" + parseFloat(v.total).toFixed(2);
                
                const cuerpo = document.getElementById("detProductosCuerpo");
                cuerpo.innerHTML = "";
                
                v.detalles.forEach(d => {
                    const tr = document.createElement("tr");
                    tr.innerHTML = `
                        <td><strong>${d.producto_codigo}</strong></td>
                        <td>${d.producto_nombre}</td>
                        <td>${d.unidadMedida}</td>
                        <td>${parseFloat(d.cantidad).toFixed(2)}</td>
                        <td>$${parseFloat(d.precio).toFixed(4)}</td>
                        <td>$${parseFloat(d.iva).toFixed(2)}</td>
                        <td><strong>$${parseFloat(d.total).toFixed(2)}</strong></td>
                    `;
                    cuerpo.appendChild(tr);
                });
                
                modalDetalle.classList.add("active");
            } else {
                alert(data.message);
            }
        } catch(e) {
            console.error(e);
            alert("Error al cargar detalles de la venta/factura.");
        }
    }
    
    function cerrarModalDetalle() {
        modalDetalle.classList.remove("active");
    }
</script>
</body>
</html>

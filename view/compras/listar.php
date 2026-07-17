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
require_once(__DIR__ . "/../../model/compra.php");
require_once(__DIR__ . "/../../model/producto.php");

$modelo = new Compra();
$productoModelo = new Producto();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$idProducto = isset($_GET["idProducto"]) ? (int)$_GET["idProducto"] : 0;

$totalCompras = $modelo->contarTotal($idProducto);
$totalPaginas = ceil($totalCompras / $limite);
$offset = ($paginaActual - 1) * $limite;

$compras = $modelo->obtenerTodosPaginado($offset, $limite, $idProducto);
$todosProductos = $productoModelo->obtenerTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/compras/">
<title>Historial de Compras</title>
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
        <h2>Compras Registradas</h2>
        <p class="descripcionSeccion">Listado de ingresos de mercadería e historial de compras. Puedes filtrar por producto específico.</p>
    </div>
</div>

<div class="barraAcciones">
    <form method="GET" action="view/compras/listar.php" class="filtro">
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
</div>

<div class="tablaPanel">
<table>
<thead>
<tr>
<?php if ($idProducto > 0) { ?>
    <th>Fecha / Hora</th>
    <th>Código</th>
    <th>Producto</th>
    <th>Cant. Comprada</th>
    <th>Precio Compra</th>
    <th>IVA Item</th>
    <th>Total Item</th>
    <th>Encargado</th>
<?php } else { ?>
    <th>ID Compra</th>
    <th>Fecha / Hora de Ingreso</th>
    <th>Subtotal</th>
    <th>IVA Total</th>
    <th>Total General</th>
    <th>Persona Encargada</th>
    <th>Acciones</th>
<?php } ?>
</tr>
</thead>
<tbody>
<?php if ($compras->num_rows > 0) { ?>
<?php while ($fila = $compras->fetch_assoc()) { ?>
<tr>
<?php if ($idProducto > 0) { ?>
    <td data-label="Fecha"><?php echo date("d/m/Y H:i:s", strtotime($fila["fecha"])); ?></td>
    <td data-label="Código"><strong><?php echo htmlspecialchars($fila["producto_codigo"]); ?></strong></td>
    <td data-label="Producto"><?php echo htmlspecialchars($fila["producto_nombre"]); ?></td>
    <td data-label="Cantidad"><?php echo number_format($fila["cantidad"], 2); ?></td>
    <td data-label="Precio">$<?php echo number_format($fila["precio_unitario"], 4); ?></td>
    <td data-label="IVA">$<?php echo number_format($fila["iva_item"], 2); ?></td>
    <td data-label="Total"><strong>$<?php echo number_format($fila["total_item"], 2); ?></strong></td>
    <td data-label="Encargado"><?php echo htmlspecialchars($fila["encargado"]); ?></td>
<?php } else { ?>
    <td data-label="ID">#<?php echo (int)$fila["idCompra"]; ?></td>
    <td data-label="Fecha"><?php echo date("d/m/Y H:i:s", strtotime($fila["fecha"])); ?></td>
    <td data-label="Subtotal">$<?php echo number_format($fila["subtotal"], 2); ?></td>
    <td data-label="IVA">$<?php echo number_format($fila["iva"], 2); ?></td>
    <td data-label="Total"><strong>$<?php echo number_format($fila["total"], 2); ?></strong></td>
    <td data-label="Encargado"><?php echo htmlspecialchars($fila["encargado"]); ?></td>
    <td data-label="Acciones">
        <button class="accion accionPrimaria" onclick="verDetalle(<?php echo (int)$fila['idCompra']; ?>)">Detalles</button>
    </td>
<?php } ?>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="<?php echo $idProducto > 0 ? 8 : 7; ?>">
    <div class="estadoVacio">
        No se registraron compras.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<?php if ($totalPaginas > 1) { ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/compras/listar.php?pag=" . ($paginaActual - 1) . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
        <a href="<?php echo urlLimpia("view/compras/listar.php?pag=" . $i . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/compras/listar.php?pag=" . ($paginaActual + 1) . ($idProducto > 0 ? "&idProducto=".$idProducto : "")); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>

<div class="modal-overlay" id="modalDetalle">
    <div class="modal-container" style="max-width: 800px;">
        <div class="modal-header">
            <h3>Detalle de la Compra <span id="lblIdCompra"></span></h3>
            <button class="modal-close" onclick="cerrarModalDetalle()">&times;</button>
        </div>
        <div class="formulario" style="padding: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px; color: rgba(255,255,255,0.8);">
                <div>
                    <p><strong>Persona encargada:</strong> <span id="detEncargado"></span></p>
                    <p><strong>Fecha de Ingreso:</strong> <span id="detFecha"></span></p>
                </div>
                <div style="text-align: right;">
                    <p><strong>Subtotal:</strong> <span id="detSubtotal"></span></p>
                    <p><strong>IVA:</strong> <span id="detIva"></span></p>
                    <p><strong>Total de Compra:</strong> <span id="detTotal" style="color: var(--color-primary); font-weight: 800; font-size: 16px;"></span></p>
                </div>
            </div>
            
            <h4 style="margin-bottom: 10px;">Productos Ingresados</h4>
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
        <div class="accionesFormulario" style="padding: 10px 20px 20px 20px;">
            <button type="button" class="botonSecundario" onclick="cerrarModalDetalle()">Cerrar</button>
        </div>
    </div>
</div>

<script>
    const modalDetalle = document.getElementById("modalDetalle");
    
    async function verDetalle(idCompra) {
        try {
            const res = await fetch(`../../controller/compraController.php?accion=detalle&id=${idCompra}`);
            const data = await res.json();
            
            if (data.success) {
                const c = data.compra;
                document.getElementById("lblIdCompra").textContent = "#" + c.idCompra;
                document.getElementById("detEncargado").textContent = `${c.u_nombres} ${c.u_apellidos} (@${c.encargado})`;
                document.getElementById("detFecha").textContent = new Date(c.fecha).toLocaleString("es-EC");
                document.getElementById("detSubtotal").textContent = "$" + parseFloat(c.subtotal).toFixed(2);
                document.getElementById("detIva").textContent = "$" + parseFloat(c.iva).toFixed(2);
                document.getElementById("detTotal").textContent = "$" + parseFloat(c.total).toFixed(2);
                
                const cuerpo = document.getElementById("detProductosCuerpo");
                cuerpo.innerHTML = "";
                
                c.detalles.forEach(d => {
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
            alert("Error al cargar detalles de la compra.");
        }
    }
    
    function cerrarModalDetalle() {
        modalDetalle.classList.remove("active");
    }
</script>
</body>
</html>

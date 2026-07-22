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
require_once(__DIR__ . "/../../model/producto.php");
require_once(__DIR__ . "/../../model/categoriaIva.php");

$modelo = new Producto();
$ivaModelo = new CategoriaIva();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$buscar = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";

$totalProductos = $modelo->contarTotal($buscar);
$totalPaginas = ceil($totalProductos / $limite);
$offset = ($paginaActual - 1) * $limite;

$productos = $modelo->obtenerTodosPaginado($offset, $limite, $buscar);
$categoriasIva = $ivaModelo->obtenerTodos();
$categoriasIvaArray = [];
while ($cat = $categoriasIva->fetch_assoc()) {
    $categoriasIvaArray[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/productos/">
<title>Productos</title>
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
    .buscador {
        display: flex;
        gap: 8px;
        flex-grow: 1;
        max-width: 500px;
    }
    .buscador input {
        flex-grow: 1;
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: white;
        border-radius: var(--radius-md);
        font-family: inherit;
    }
    .buscador input:focus {
        border-color: var(--color-primary);
        outline: none;
        background: rgba(255, 255, 255, 0.08);
    }
</style>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Administración</p>
        <h2>Productos e Inventario</h2>
        <p class="descripcionSeccion">CRUD de productos de cualquier tipo, configurando su unidad de medida, precio y categoría de IVA.</p>
    </div>
    <button class="botonNuevo" onclick="abrirModalCrear()">Nuevo producto</button>
</div>

<?php
if (isset($_GET["mensaje"])) {
    if ($_GET["mensaje"] == "ok") {
        echo '<div class="mensajeExito">Producto registrado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "modificado") {
        echo '<div class="mensajeExito">Producto actualizado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "eliminado") {
        echo '<div class="mensajeExito">Producto eliminado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "existe") {
        echo '<div class="mensajeError">El código del producto ya está registrado.</div>';
    }
    if ($_GET["mensaje"] == "tiene_historico") {
        echo '<div class="mensajeError">No se puede eliminar el producto porque posee historial de compras o ventas.</div>';
    }
    if ($_GET["mensaje"] == "error") {
        echo '<div class="mensajeError">Ha ocurrido un error al procesar la operación.</div>';
    }
}
?>

<div class="barraAcciones">
    <form method="GET" action="<?php echo urlLimpia('view/productos/listar.php'); ?>" class="buscador">
        <input type="text" name="buscar" placeholder="Buscar por código, nombre o descripción..." value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit" class="botonNuevo" style="background: var(--color-surface); border: 1px solid var(--color-border); color: white;">Buscar</button>
        <?php if ($buscar !== "") { ?>
            <a href="<?php echo urlLimpia('view/productos/listar.php'); ?>" class="botonNuevo" style="background: rgba(255,0,0,0.2); border: 1px solid rgba(255,0,0,0.3); color: white; display: flex; align-items: center; justify-content: center; text-decoration: none;">Limpiar</a>
        <?php } ?>
    </form>
</div>

<div class="tablaPanel">
<table>
<thead>
<tr>
<th>Código</th>
<th>Nombre</th>
<th>Descripción</th>
<th>U. Medida</th>
<th>Precio Unitario</th>
<th>Stock Actual</th>
<th>Tarifa IVA</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if ($productos->num_rows > 0) { ?>
<?php while ($fila = $productos->fetch_assoc()) { ?>
<tr>
<td data-label="Código"><strong><?php echo htmlspecialchars($fila["codigo"]); ?></strong></td>
<td data-label="Nombre"><?php echo htmlspecialchars($fila["nombre"]); ?></td>
<td data-label="Descripción"><?php echo htmlspecialchars($fila["descripcion"] ?? '-'); ?></td>
<td data-label="Medida"><span class="insignia rol"><?php echo htmlspecialchars($fila["unidadMedida"] ?? $fila["unidadmedida"] ?? '-'); ?></span></td>
<td data-label="Precio">$<?php echo number_format($fila["precio"], 4); ?></td>
<td data-label="Stock">
    <strong style="color: <?php echo $fila["stock"] <= 5 ? '#f43f5e' : '#10b981'; ?>">
        <?php echo number_format($fila["stock"], 2); ?>
    </strong>
</td>
<td data-label="IVA"><span class="insignia activo"><?php echo htmlspecialchars($fila["categoriaIva"]); ?></span></td>
<td data-label="Acciones">
    <div class="accionesTabla">
        <button class="accion accionPrimaria" 
                onclick="abrirModalModificar({
                    idProducto: <?php echo (int) $fila['idProducto']; ?>,
                    codigo: '<?php echo addslashes($fila['codigo']); ?>',
                    nombre: '<?php echo addslashes($fila['nombre']); ?>',
                    descripcion: '<?php echo addslashes($fila['descripcion']); ?>',
                    precio: <?php echo (float)$fila['precio']; ?>,
                    unidadMedida: '<?php echo addslashes($fila['unidadMedida']); ?>',
                    idCategoriaIva: <?php echo (int)$fila['idCategoriaIva']; ?>
                })">Modificar</button>
        <a class="accion accionPeligro" 
           href="<?php echo urlLimpia("controller/productoController.php?accion=eliminar&id=" . (int) $fila["idProducto"]); ?>" 
           onclick="return confirm('¿Estás seguro de eliminar este producto?')">Eliminar</a>
    </div>
</td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="8">
    <div class="estadoVacio">
        No se encontraron productos registrados.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<?php if ($totalPaginas > 1) { ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/productos/listar.php?pag=" . ($paginaActual - 1) . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
        <a href="<?php echo urlLimpia("view/productos/listar.php?pag=" . $i . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/productos/listar.php?pag=" . ($paginaActual + 1) . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>

<div class="modal-overlay" id="modalCrear">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Nuevo Producto</h3>
            <button class="modal-close" onclick="cerrarModalCrear()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/productoController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="guardar">
            <div class="gridFormulario">
                <div>
                    <label for="codigo">Código del Producto</label>
                    <input type="text" name="codigo" id="codigo" required placeholder="Ej. PROD001">
                </div>
                <div>
                    <label for="nombre">Nombre</label>
                    <input type="text" name="nombre" id="nombre" required placeholder="Ej. Coca Cola 1.5L">
                </div>
                <div>
                    <label for="descripcion">Descripción</label>
                    <input type="text" name="descripcion" id="descripcion" placeholder="Ej. Bebida gaseosa familiar">
                </div>
                <div>
                    <label for="unidadMedida">Unidad de Medida</label>
                    <input type="text" name="unidadMedida" id="unidadMedida" required placeholder="Ej. Unidad, Kg, Litro, Caja">
                </div>
                <div>
                    <label for="precio">Precio de Venta</label>
                    <input type="number" name="precio" id="precio" step="0.0001" min="0.0001" required placeholder="0.0000">
                </div>
                <div>
                    <label for="idCategoriaIva">Tarifa de IVA</label>
                    <select name="idCategoriaIva" id="idCategoriaIva" required>
                        <option value="">Selecciona tarifa</option>
                        <?php foreach ($categoriasIvaArray as $cat) { ?>
                            <option value="<?php echo (int)$cat['idCategoriaIva']; ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label for="stock">Stock Inicial</label>
                    <input type="number" name="stock" id="stock" step="1" min="0" value="0">
                </div>
            </div>
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalCrear()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar">
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="modalModificar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Modificar Producto</h3>
            <button class="modal-close" onclick="cerrarModalModificar()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/productoController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="modificar">
            <input type="hidden" name="idProducto" id="mod_idProducto">
            <div class="gridFormulario">
                <div>
                    <label for="mod_codigo">Código del Producto</label>
                    <input type="text" name="codigo" id="mod_codigo" required>
                </div>
                <div>
                    <label for="mod_nombre">Nombre</label>
                    <input type="text" name="nombre" id="mod_nombre" required>
                </div>
                <div>
                    <label for="mod_descripcion">Descripción</label>
                    <input type="text" name="descripcion" id="mod_descripcion">
                </div>
                <div>
                    <label for="mod_unidadMedida">Unidad de Medida</label>
                    <input type="text" name="unidadMedida" id="mod_unidadMedida" required>
                </div>
                <div>
                    <label for="mod_precio">Precio de Venta</label>
                    <input type="number" name="precio" id="mod_precio" step="0.0001" min="0.0001" required>
                </div>
                <div>
                    <label for="mod_idCategoriaIva">Tarifa de IVA</label>
                    <select name="idCategoriaIva" id="mod_idCategoriaIva" required>
                        <?php foreach ($categoriasIvaArray as $cat) { ?>
                            <option value="<?php echo (int)$cat['idCategoriaIva']; ?>">
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalModificar()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar cambios">
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalCrear() {
        document.getElementById("modalCrear").classList.add("active");
    }
    function cerrarModalCrear() {
        document.getElementById("modalCrear").classList.remove("active");
    }
    function abrirModalModificar(producto) {
        document.getElementById("mod_idProducto").value = producto.idProducto;
        document.getElementById("mod_codigo").value = producto.codigo;
        document.getElementById("mod_nombre").value = producto.nombre;
        document.getElementById("mod_descripcion").value = producto.descripcion;
        document.getElementById("mod_precio").value = producto.precio;
        document.getElementById("mod_unidadMedida").value = producto.unidadMedida;
        document.getElementById("mod_idCategoriaIva").value = producto.idCategoriaIva;
        document.getElementById("modalModificar").classList.add("active");
    }
    function cerrarModalModificar() {
        document.getElementById("modalModificar").classList.remove("active");
    }
</script>
<script src="../../controller/productoValidacion.js"></script>
</body>
</html>

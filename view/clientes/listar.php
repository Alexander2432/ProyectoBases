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
require_once(__DIR__ . "/../../model/cliente.php");

$modelo = new Cliente();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$buscar = isset($_GET["buscar"]) ? trim($_GET["buscar"]) : "";

$totalClientes = $modelo->contarTotal($buscar);
$totalPaginas = ceil($totalClientes / $limite);
$offset = ($paginaActual - 1) * $limite;

$clientes = $modelo->obtenerTodosPaginado($offset, $limite, $buscar);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/clientes/">
<title>Clientes</title>
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
        <h2>Clientes</h2>
        <p class="descripcionSeccion">Registra y administra la información de los clientes para la facturación.</p>
    </div>
    <button class="botonNuevo" onclick="abrirModalCrear()">Nuevo cliente</button>
</div>

<?php
if (isset($_GET["mensaje"])) {
    if ($_GET["mensaje"] == "ok") {
        echo '<div class="mensajeExito">Cliente registrado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "modificado") {
        echo '<div class="mensajeExito">Cliente actualizado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "eliminado") {
        echo '<div class="mensajeExito">Cliente eliminado correctamente.</div>';
    }
    if ($_GET["mensaje"] == "existe") {
        echo '<div class="mensajeError">La cédula o RUC ya está registrada.</div>';
    }
    if ($_GET["mensaje"] == "cedula_invalida") {
        echo '<div class="mensajeError">La cédula o RUC ingresado no cumple con el formato válido.</div>';
    }
    if ($_GET["mensaje"] == "tiene_ventas") {
        echo '<div class="mensajeError">No se puede eliminar el cliente porque tiene ventas asociadas en el historial.</div>';
    }
    if ($_GET["mensaje"] == "error") {
        echo '<div class="mensajeError">Ha ocurrido un error al procesar la operación.</div>';
    }
}
?>

<div class="barraAcciones">
    <form method="GET" action="view/clientes/listar.php" class="buscador">
        <input type="text" name="buscar" placeholder="Buscar por cédula, nombre o apellido..." value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit" class="botonNuevo" style="background: var(--color-surface); border: 1px solid var(--color-border); color: white;">Buscar</button>
        <?php if ($buscar !== "") { ?>
            <a href="view/clientes/listar.php" class="botonNuevo" style="background: rgba(255,0,0,0.2); border: 1px solid rgba(255,0,0,0.3); color: white; display: flex; align-items: center; justify-content: center; text-decoration: none;">Limpiar</a>
        <?php } ?>
    </form>
</div>

<div class="tablaPanel">
<table>
<thead>
<tr>
<th>Cédula / RUC</th>
<th>Nombres</th>
<th>Apellidos</th>
<th>Correo electrónico</th>
<th>Teléfono</th>
<th>Dirección</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if ($clientes->num_rows > 0) { ?>
<?php while ($fila = $clientes->fetch_assoc()) { ?>
<tr>
<td data-label="Identificación"><strong><?php echo htmlspecialchars($fila["cedula"]); ?></strong></td>
<td data-label="Nombres"><?php echo htmlspecialchars($fila["nombres"]); ?></td>
<td data-label="Apellidos"><?php echo htmlspecialchars($fila["apellidos"]); ?></td>
<td data-label="Correo"><?php echo htmlspecialchars($fila["correo"] ?? '-'); ?></td>
<td data-label="Teléfono"><?php echo htmlspecialchars($fila["telefono"] ?? '-'); ?></td>
<td data-label="Dirección"><?php echo htmlspecialchars($fila["direccion"] ?? '-'); ?></td>
<td data-label="Acciones">
    <div class="accionesTabla">
        <button class="accion" style="background-color: #10b981; color: white;" 
                onclick="verVentasTotales(<?php echo (int) $fila['idCliente']; ?>, '<?php echo addslashes($fila['nombres'] . ' ' . $fila['apellidos']); ?>')">Ventas Totales</button>
        <button class="accion accionPrimaria" 
                onclick="abrirModalModificar({
                    idCliente: <?php echo (int) $fila['idCliente']; ?>,
                    cedula: '<?php echo addslashes($fila['cedula']); ?>',
                    nombres: '<?php echo addslashes($fila['nombres']); ?>',
                    apellidos: '<?php echo addslashes($fila['apellidos']); ?>',
                    correo: '<?php echo addslashes($fila['correo']); ?>',
                    telefono: '<?php echo addslashes($fila['telefono']); ?>',
                    direccion: '<?php echo addslashes($fila['direccion']); ?>'
                })">Modificar</button>
        <a class="accion accionPeligro" 
           href="<?php echo urlLimpia("controller/clienteController.php?accion=eliminar&id=" . (int) $fila["idCliente"]); ?>" 
           onclick="return confirm('¿Estás seguro de eliminar este cliente?')">Eliminar</a>
    </div>
</td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
<td colspan="7">
    <div class="estadoVacio">
        No se encontraron clientes registrados.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>

<?php if ($totalPaginas > 1) { ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/clientes/listar.php?pag=" . ($paginaActual - 1) . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for ($i = 1; $i <= $totalPaginas; $i++) { ?>
        <a href="<?php echo urlLimpia("view/clientes/listar.php?pag=" . $i . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/clientes/listar.php?pag=" . ($paginaActual + 1) . ($buscar !== "" ? "&buscar=".urlencode($buscar) : "")); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>

<div class="modal-overlay" id="modalCrear">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Nuevo cliente</h3>
            <button class="modal-close" onclick="cerrarModalCrear()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/clienteController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="guardar">
            <div class="gridFormulario">
                <div>
                    <label for="cedula">Cédula / RUC</label>
                    <input type="text" name="cedula" id="cedula" maxlength="13" minlength="10" inputmode="numeric" required placeholder="Ej. 0102030400 o 0102030400001">
                    <small class="ayudaCampo">Debe ingresar una cédula válida (10 dígitos) o RUC (13 dígitos).</small>
                </div>
                <div>
                    <label for="nombres">Nombres</label>
                    <input type="text" name="nombres" id="nombres" required placeholder="Ej. Juan Carlos">
                </div>
                <div>
                    <label for="apellidos">Apellidos</label>
                    <input type="text" name="apellidos" id="apellidos" required placeholder="Ej. Perez Lopez">
                </div>
                <div>
                    <label for="correo">Correo electrónico</label>
                    <input type="email" name="correo" id="correo" placeholder="correo@ejemplo.com">
                </div>
                <div>
                    <label for="telefono">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" maxlength="10" placeholder="Ej. 0999999999">
                </div>
                <div>
                    <label for="direccion">Dirección</label>
                    <input type="text" name="direccion" id="direccion" placeholder="Ej. Av. de las Americas 2-40">
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
            <h3>Modificar cliente</h3>
            <button class="modal-close" onclick="cerrarModalModificar()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/clienteController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="modificar">
            <input type="hidden" name="idCliente" id="mod_idCliente">
            <div class="gridFormulario">
                <div>
                    <label for="mod_cedula">Cédula / RUC</label>
                    <input type="text" name="cedula" id="mod_cedula" maxlength="13" minlength="10" inputmode="numeric" required>
                    <small class="ayudaCampo">Cédula o RUC del cliente.</small>
                </div>
                <div>
                    <label for="mod_nombres">Nombres</label>
                    <input type="text" name="nombres" id="mod_nombres" required>
                </div>
                <div>
                    <label for="mod_apellidos">Apellidos</label>
                    <input type="text" name="apellidos" id="mod_apellidos" required>
                </div>
                <div>
                    <label for="mod_correo">Correo electrónico</label>
                    <input type="email" name="correo" id="mod_correo">
                </div>
                <div>
                    <label for="mod_telefono">Teléfono</label>
                    <input type="text" name="telefono" id="mod_telefono" maxlength="10">
                </div>
                <div>
                    <label for="mod_direccion">Dirección</label>
                    <input type="text" name="direccion" id="mod_direccion">
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
    function abrirModalModificar(cliente) {
        document.getElementById("mod_idCliente").value = cliente.idCliente;
        document.getElementById("mod_cedula").value = cliente.cedula;
        document.getElementById("mod_nombres").value = cliente.nombres;
        document.getElementById("mod_apellidos").value = cliente.apellidos;
        document.getElementById("mod_correo").value = cliente.correo;
        document.getElementById("mod_telefono").value = cliente.telefono;
        document.getElementById("mod_direccion").value = cliente.direccion;
        document.getElementById("modalModificar").classList.add("active");
    }
    function cerrarModalModificar() {
        document.getElementById("modalModificar").classList.remove("active");
    }
    async function verVentasTotales(idCliente, nombreCliente) {
        try {
            const res = await fetch(`../../controller/clienteController.php?accion=ajax_ventas_totales&id=${idCliente}`);
            const data = await res.json();
            if (data.success) {
                alert(`Las ventas totales del cliente ${nombreCliente} (calculadas mediante procedimiento almacenado) son: $${parseFloat(data.total).toFixed(2)}`);
            } else {
                alert("No se pudo calcular el total de ventas.");
            }
        } catch(e) {
            console.error(e);
            alert("Error al conectar con el servidor.");
        }
    }
</script>
<script src="../../controller/clienteValidacion.js"></script>
</body>
</html>

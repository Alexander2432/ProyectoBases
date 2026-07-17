<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
require_once(__DIR__ . "/../../model/modeloRoles.php");

$modelo = new Rol();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$totalRoles = $modelo->contarTotal();
$totalPaginas = ceil($totalRoles / $limite);
$offset = ($paginaActual - 1) * $limite;

$roles = $modelo->listarPaginado($offset, $limite);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/roles/">
<title>Roles &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Administraci&oacute;n</p>
        <h2>Roles del sistema</h2>
        <p class="descripcionSeccion">Gestiona los perfiles y los permisos que tendr&aacute; cada tipo de usuario.</p>
    </div>
    <button class="botonNuevo" onclick="abrirModalCrear()">Crear rol</button>
</div>

<?php
if(isset($_GET["mensaje"])){
    if($_GET["mensaje"]=="modificado"){
        echo '<div class="mensajeExito">Rol actualizado correctamente.</div>';
    }
    if($_GET["mensaje"]=="ok"){
        echo '<div class="mensajeExito">Rol creado correctamente.</div>';
    }
    if($_GET["mensaje"]=="eliminado"){
        echo '<div class="mensajeExito">Rol eliminado correctamente.</div>';
    }
    if($_GET["mensaje"]=="tiene_usuarios"){
        echo '<div class="mensajeError">No se puede eliminar el rol porque tiene usuarios asignados.</div>';
    }
    if($_GET["mensaje"]=="admin_nodo"){
        echo '<div class="mensajeError">No puedes eliminar el rol de Administrador.</div>';
    }
    if($_GET["mensaje"]=="error"){
        echo '<div class="mensajeError">No se pudo completar la operaci&oacute;n. Revisa los datos e intenta nuevamente.</div>';
    }
}
?>

<div class="tablaPanel">
<table>
<thead>
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Descripci&oacute;n</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if($roles->num_rows > 0){ ?>
<?php while($fila = $roles->fetch_assoc()){ ?>
<tr>
<td data-label="ID"><?php echo (int) $fila["idRol"]; ?></td>
<td data-label="Nombre"><strong><?php echo htmlspecialchars($fila["nombre"]); ?></strong></td>
<td data-label="Descripci&oacute;n"><?php echo htmlspecialchars($fila["descripcion"]); ?></td>
<td data-label="Acciones">
    <div class="accionesTabla">
        <button class="accion accionPrimaria" 
                onclick="abrirModalModificar({
                    idRol: <?php echo (int) $fila['idRol']; ?>,
                    nombre: '<?php echo addslashes($fila['nombre']); ?>',
                    descripcion: '<?php echo addslashes($fila['descripcion']); ?>'
                })">Modificar</button>
        <a class="accion" href="<?php echo urlLimpia("view/permisos/asignar.php?idRol=" . (int) $fila["idRol"]); ?>">Permisos</a>
        <?php if($fila["idRol"] != 1){ ?>
            <a class="accion accionPeligro" href="javascript:void(0)" onclick="confirmarEliminar(<?php echo (int) $fila['idRol']; ?>, '<?php echo addslashes($fila['nombre']); ?>')">Eliminar</a>
        <?php } ?>
    </div>
</td>
</tr>
<?php } ?>
<?php }else{ ?>
<tr>
<td colspan="4">
    <div class="estadoVacio">
        <div class="icono">--</div>
        No hay roles registrados.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>


<?php if($totalPaginas > 1){ ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/roles/listar.php?pag=" . ($paginaActual - 1)); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for($i = 1; $i <= $totalPaginas; $i++){ ?>
        <a href="<?php echo urlLimpia("view/roles/listar.php?pag=" . $i); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/roles/listar.php?pag=" . ($paginaActual + 1)); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>


<div class="modal-overlay" id="modalCrear">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Crear nuevo rol</h3>
            <button class="modal-close" onclick="cerrarModalCrear()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/rolController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="guardar">
            
            <label>Nombre del rol</label>
            <input type="text" name="nombre" placeholder="Ej. Supervisor" maxlength="50" required>
            
            <label>Descripci&oacute;n</label>
            <textarea name="descripcion" rows="4" placeholder="Describe brevemente qu&eacute; puede hacer este rol" required></textarea>
            
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalCrear()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar rol">
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalModificar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Modificar rol</h3>
            <button class="modal-close" onclick="cerrarModalModificar()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/rolController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="modificar">
            <input type="hidden" name="idRol" id="mod_idRol">
            
            <label>Nombre del rol</label>
            <input type="text" name="nombre" id="mod_nombre" required>
            
            <label>Descripci&oacute;n</label>
            <textarea name="descripcion" id="mod_descripcion" rows="4" required></textarea>
            
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
function abrirModalModificar(datos) {
    document.getElementById("mod_idRol").value = datos.idRol;
    document.getElementById("mod_nombre").value = datos.nombre;
    document.getElementById("mod_descripcion").value = datos.descripcion;
    document.getElementById("modalModificar").classList.add("active");
}
function cerrarModalModificar() {
    document.getElementById("modalModificar").classList.remove("active");
}
function confirmarEliminar(id, nombre) {
    if (confirm("¿Estás seguro de que deseas eliminar el rol '" + nombre + "'? Se eliminarán también sus permisos asociados.")) {
        window.location.href = "../../controller/rolController.php?accion=eliminar&id=" + id;
    }
}
window.addEventListener("DOMContentLoaded", function() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("crear")) {
        abrirModalCrear();
    }
});
</script>
</body>
</html>

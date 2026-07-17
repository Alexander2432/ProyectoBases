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
require_once(__DIR__ . "/../../model/usuario.php");
require_once(__DIR__ . "/../../model/modeloRoles.php");

$modelo = new Usuario();
$rolModelo = new Rol();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$totalUsuarios = $modelo->contarTotal();
$totalPaginas = ceil($totalUsuarios / $limite);
$offset = ($paginaActual - 1) * $limite;

$usuarios = $modelo->listarPaginado($offset, $limite);
$rolesCreate = $rolModelo->listarSinAdministrador();
$rolesModify = $rolModelo->listarTodos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/usuarios/">
<title>Usuarios &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Administraci&oacute;n</p>
        <h2>Usuarios</h2>
        <p class="descripcionSeccion">Consulta, actualiza y controla el acceso de los usuarios registrados.</p>
    </div>
    <button class="botonNuevo" onclick="abrirModalCrear()">Crear usuario</button>
</div>

<?php
if(isset($_GET["mensaje"])){
    if($_GET["mensaje"]=="modificado"){
        echo '<div class="mensajeExito">Usuario actualizado correctamente.</div>';
    }
    if($_GET["mensaje"]=="estado"){
        echo '<div class="mensajeExito">Estado actualizado correctamente.</div>';
    }
    if($_GET["mensaje"]=="propio"){
        echo '<div class="mensajeError">No puedes cambiar el estado de tu propio usuario.</div>';
    }
    if($_GET["mensaje"]=="ok"){
        echo '<div class="mensajeExito">Usuario creado correctamente.</div>';
    }
    if($_GET["mensaje"]=="existe"){
        echo '<div class="mensajeError">El usuario o la c&eacute;dula ya existe.</div>';
    }
    if($_GET["mensaje"]=="cedula"){
        echo '<div class="mensajeError">La c&eacute;dula ingresada no es v&aacute;lida.</div>';
    }
    if($_GET["mensaje"]=="password_invalida"){
        echo '<div class="mensajeError">La contrase&ntilde;a debe tener al menos 8 caracteres, una letra may&uacute;scula y un caracter especial.</div>';
    }
}
?>

<div class="tablaPanel">
<table>
<thead>
<tr>
<th>Usuario</th>
<th>Nombres</th>
<th>Apellidos</th>
<th>Correo</th>
<th>C&eacute;dula</th>
<th>Tel&eacute;fono</th>
<th>Rol</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if($usuarios->num_rows > 0){ ?>
<?php while($fila = $usuarios->fetch_assoc()){ ?>
<tr>
<td data-label="Usuario"><strong><?php echo htmlspecialchars($fila["usuario"]); ?></strong></td>
<td data-label="Nombres"><?php echo htmlspecialchars($fila["nombres"]); ?></td>
<td data-label="Apellidos"><?php echo htmlspecialchars($fila["apellidos"]); ?></td>
<td data-label="Correo"><?php echo htmlspecialchars($fila["correo"]); ?></td>
<td data-label="C&eacute;dula"><?php echo htmlspecialchars($fila["cedula"] ?? ""); ?></td>
<td data-label="Tel&eacute;fono"><?php echo htmlspecialchars($fila["telefono"]); ?></td>
<td data-label="Rol"><span class="insignia rol"><?php echo htmlspecialchars($fila["rol"]); ?></span></td>
<td data-label="Estado">
    <span class="insignia <?php echo $fila["estado"]=="Activo" ? "activo" : "inactivo"; ?>">
        <?php echo htmlspecialchars($fila["estado"]); ?>
    </span>
</td>
<td data-label="Acciones">
    <div class="accionesTabla">
        <button class="accion accionPrimaria" 
                onclick="abrirModalModificar({
                    idUsuario: <?php echo (int) $fila['idUsuario']; ?>,
                    usuario: '<?php echo addslashes($fila['usuario']); ?>',
                    nombres: '<?php echo addslashes($fila['nombres']); ?>',
                    apellidos: '<?php echo addslashes($fila['apellidos']); ?>',
                    correo: '<?php echo addslashes($fila['correo']); ?>',
                    telefono: '<?php echo addslashes($fila['telefono']); ?>',
                    cedula: '<?php echo addslashes($fila['cedula'] ?? ''); ?>',
                    idRol: <?php echo (int) $fila['idRol']; ?>,
                    rolNombre: '<?php echo addslashes($fila['rol']); ?>'
                })">Modificar</button>
        <?php if($fila["idUsuario"] != $_SESSION["idUsuario"]){ ?>
            <a class="accion <?php echo $fila["estado"]=="Activo" ? "accionPeligro" : ""; ?>" href="<?php echo urlLimpia("controller/usuarioController.php?accion=estado&id=" . (int) $fila["idUsuario"]); ?>">
                <?php echo $fila["estado"]=="Activo" ? "Inactivar" : "Activar"; ?>
            </a>
        <?php }else{ ?>
            <span class="textoMuted">Actual</span>
        <?php } ?>
    </div>
</td>
</tr>
<?php } ?>
<?php }else{ ?>
<tr>
<td colspan="9">
    <div class="estadoVacio">
        <div class="icono">--</div>
        No hay usuarios registrados.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>


<?php if($totalPaginas > 1){ ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/usuarios/listar.php?pag=" . ($paginaActual - 1)); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for($i = 1; $i <= $totalPaginas; $i++){ ?>
        <a href="<?php echo urlLimpia("view/usuarios/listar.php?pag=" . $i); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/usuarios/listar.php?pag=" . ($paginaActual + 1)); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>


<div class="modal-overlay" id="modalCrear">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Crear nuevo usuario</h3>
            <button class="modal-close" onclick="cerrarModalCrear()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/usuarioController.php"); ?>" method="POST" id="formUsuario" class="formulario">
            <input type="hidden" name="accion" value="guardar">
            <div class="gridFormulario">
                <div>
                    <label>Nombres</label>
                    <input type="text" name="nombres" id="nombres" placeholder="Ej. Alexander Maza" required>
                </div>
                <div>
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" id="apellidos" placeholder="Ej. Flores" required>
                </div>
                <div>
                    <label>Correo electr&oacute;nico</label>
                    <input type="email" name="correo" id="correo" placeholder="correo@dominio.com" required>
                </div>
                <div>
                    <label for="cedula">C&eacute;dula</label>
                    <input type="text" name="cedula" id="cedula" maxlength="10" minlength="10" inputmode="numeric" autocomplete="off" pattern="[0-9]{10}" placeholder="Ej. 0102030400" title="Ingrese una c&eacute;dula ecuatoriana de 10 d&iacute;gitos" required>
                    <small class="ayudaCampo">Debe ser una c&eacute;dula v&aacute;lida de 10 d&iacute;gitos.</small>
                </div>
                <div>
                    <label>Tel&eacute;fono</label>
                    <input type="text" name="telefono" id="telefono" maxlength="10" placeholder="09XXXXXXXX" required>
                </div>
                <div>
                    <label>Usuario</label>
                    <input type="text" name="usuario" id="usuario" placeholder="Nombre de usuario" required>
                </div>
                <div>
                    <label>Rol</label>
                    <select name="idRol" id="idRol" required>
                        <option value="">Selecciona un rol</option>
                        <?php while($rol = $rolesCreate->fetch_assoc()){ ?>
                        <option value="<?php echo (int) $rol["idRol"]; ?>">
                            <?php echo htmlspecialchars($rol["nombre"]); ?>
                        </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label>Contrase&ntilde;a</label>
                    <input type="password" name="password" id="password" placeholder="M&iacute;nimo 8 car., 1 may&uacute;s., 1 esp." required>
                    <small class="ayudaCampo">Debe contener al menos 8 caracteres, una may&uacute;scula y un caracter especial.</small>
                </div>
                <div>
                    <label>Confirmar contrase&ntilde;a</label>
                    <input type="password" id="confirmar" placeholder="Repite la contrase&ntilde;a" required>
                </div>
            </div>
            <p id="mensaje" class="mensajeFormulario"></p>
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalCrear()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar usuario">
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalModificar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Modificar usuario</h3>
            <button class="modal-close" onclick="cerrarModalModificar()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/usuarioController.php"); ?>" method="POST" class="formulario">
            <input type="hidden" name="accion" value="modificar">
            <input type="hidden" name="idUsuario" id="mod_idUsuario">
            <div class="gridFormulario">
                <div>
                    <label>Usuario (No editable)</label>
                    <input type="text" name="usuario" id="mod_usuario" readonly class="campoDeshabilitado">
                </div>
                <div>
                    <label>Rol</label>
                    <select name="idRol" id="mod_idRol" required>
                        <option value="">Selecciona un rol</option>
                        <?php while($rol = $rolesModify->fetch_assoc()){ ?>
                        <option value="<?php echo (int) $rol["idRol"]; ?>">
                            <?php echo htmlspecialchars($rol["nombre"]); ?>
                        </option>
                        <?php } ?>
                    </select>
                    <small class="ayudaCampo" id="mod_rolInfo" style="display:none;">El rol de Administrador no se puede cambiar.</small>
                </div>
                <div>
                    <label>Nombres</label>
                    <input type="text" name="nombres" id="mod_nombres" required>
                </div>
                <div>
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" id="mod_apellidos" required>
                </div>
                <div>
                    <label>Correo electr&oacute;nico</label>
                    <input type="email" name="correo" id="mod_correo" required>
                </div>
                <div>
                    <label>Tel&eacute;fono</label>
                    <input type="text" name="telefono" id="mod_telefono" maxlength="10" required>
                </div>
            </div>
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalModificar()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Actualizar usuario">
            </div>
        </form>
    </div>
</div>

<script src="../../controller/validarUsuario.js"></script>
<script>
function abrirModalCrear() {
    document.getElementById("modalCrear").classList.add("active");
}
function cerrarModalCrear() {
    document.getElementById("modalCrear").classList.remove("active");
}
function abrirModalModificar(datos) {
    document.getElementById("mod_idUsuario").value = datos.idUsuario;
    document.getElementById("mod_usuario").value = datos.usuario;
    document.getElementById("mod_nombres").value = datos.nombres;
    document.getElementById("mod_apellidos").value = datos.apellidos;
    document.getElementById("mod_correo").value = datos.correo;
    document.getElementById("mod_telefono").value = datos.telefono;
    
    var selectRol = document.getElementById("mod_idRol");
    selectRol.value = datos.idRol;
    
    
    var esAdmin = (datos.idRol === 1 || datos.usuario === 'admin');
    if (esAdmin) {
        selectRol.disabled = true;
        
        var hiddenRol = document.getElementById("mod_idRol_hidden");
        if (!hiddenRol) {
            hiddenRol = document.createElement("input");
            hiddenRol.type = "hidden";
            hiddenRol.name = "idRol";
            hiddenRol.id = "mod_idRol_hidden";
            selectRol.parentNode.appendChild(hiddenRol);
        }
        hiddenRol.value = datos.idRol;
        document.getElementById("mod_rolInfo").style.display = "block";
    } else {
        selectRol.disabled = false;
        var hiddenRol = document.getElementById("mod_idRol_hidden");
        if (hiddenRol) {
            hiddenRol.remove();
        }
        document.getElementById("mod_rolInfo").style.display = "none";
    }

    document.getElementById("modalModificar").classList.add("active");
}
function cerrarModalModificar() {
    document.getElementById("modalModificar").classList.remove("active");
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

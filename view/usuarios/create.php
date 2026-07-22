<?php
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
require_once(__DIR__ . "/../../model/modeloRoles.php");
$rolModelo = new Rol();
$roles = $rolModelo->listarSinAdministrador();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/usuarios/">
<script src="../../controller/logout.js"></script>
<title>Crear usuario &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Nuevo acceso</p>
        <h2>Crear usuario</h2>
        <p class="descripcionSeccion">Registra datos personales, credenciales y rol inicial.</p>
    </div>
    <a class="botonSecundario" href="<?php echo urlLimpia("view/usuarios/listar.php"); ?>">Volver</a>
</div>

<?php
if(isset($_GET["mensaje"])){
    if($_GET["mensaje"]=="ok"){
        echo '<div class="mensajeExito">Usuario creado correctamente.</div>';
    } else if($_GET["mensaje"]=="existe"){
        echo '<div class="mensajeError">El usuario o la c&eacute;dula ya existe.</div>';
    } else {
        echo '<div class="mensajeError">' . htmlspecialchars(urldecode($_GET["mensaje"])) . '</div>';
    }
}
?>

<form action="<?php echo urlLimpia("controller/usuarioController.php"); ?>" method="POST" id="formUsuario" class="formulario formularioPanel" novalidate>
    <input type="hidden" name="accion" value="guardar">

    <div class="gridFormulario">
        <div>
            <label>Nombres</label>
            <input type="text" name="nombres" id="nombres" placeholder="Ej. Jardel Alexander" required>
        </div>
        <div>
            <label>Apellidos</label>
            <input type="text" name="apellidos" id="apellidos" placeholder="Ej. Maza Flores" required>
        </div>
        <div>
            <label>Correo electr&oacute;nico</label>
            <input type="text" name="correo" id="correo" placeholder="correo@dominio.com" required>
        </div>
        <div>
            <label for="cedula">C&eacute;dula</label>
            <input type="text" name="cedula" id="cedula" placeholder="Ej. 0102030400" required>
            <small class="ayudaCampo">Debe ser una c&eacute;dula ecuatoriana v&aacute;lida de 10 d&iacute;gitos.</small>
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
                <?php while($rol = $roles->fetch_assoc()){ ?>
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
        <a class="botonSecundario" href="<?php echo urlLimpia("view/usuarios/listar.php"); ?>">Cancelar</a>
        <input type="submit" value="Guardar usuario">
    </div>
</form>
</main>
<script src="../../controller/validarUsuario.js"></script>
</body>
</html>

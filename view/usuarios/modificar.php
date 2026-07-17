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
$roles = $rolModelo->listarTodos();

$idUsuario = (int) ($_GET["id"] ?? 0);
$resultado = $idUsuario > 0 ? $modelo->buscarPorId($idUsuario) : false;
$fila = $resultado ? $resultado->fetch_assoc() : null;

if(!$fila){
    header("Location: " . urlLimpia("view/usuarios/listar.php"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/usuarios/">
<title>Modificar usuario &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Edici&oacute;n</p>
        <h2>Modificar usuario</h2>
        <p class="descripcionSeccion">Actualiza los datos de contacto y el rol asignado.</p>
    </div>
    <a class="botonSecundario" href="<?php echo urlLimpia("view/usuarios/listar.php"); ?>">Volver</a>
</div>

<form action="<?php echo urlLimpia("controller/usuarioController.php"); ?>" method="POST" class="formulario formularioPanel">
<input type="hidden" name="accion" value="modificar">
<input type="hidden" name="idUsuario" value="<?php echo (int) $fila["idUsuario"]; ?>">

<div class="gridFormulario">
    <div>
        <label>Usuario</label>
        <input type="text" name="usuario" value="<?php echo htmlspecialchars($fila["usuario"]); ?>" required>
    </div>
    <div>
        <label>Rol</label>
        <select name="idRol" required>
            <option value="">Selecciona un rol</option>
            <?php while($rol = $roles->fetch_assoc()){ ?>
            <option value="<?php echo (int) $rol["idRol"]; ?>" <?php echo ((int)$rol["idRol"] === (int)$fila["idRol"]) ? "selected" : ""; ?>>
                <?php echo htmlspecialchars($rol["nombre"]); ?>
            </option>
            <?php } ?>
        </select>
    </div>
    <div>
        <label>Nombres</label>
        <input type="text" name="nombres" value="<?php echo htmlspecialchars($fila["nombres"]); ?>" required>
    </div>
    <div>
        <label>Apellidos</label>
        <input type="text" name="apellidos" value="<?php echo htmlspecialchars($fila["apellidos"]); ?>" required>
    </div>
    <div>
        <label>Correo electr&oacute;nico</label>
        <input type="email" name="correo" value="<?php echo htmlspecialchars($fila["correo"]); ?>" required>
    </div>
    <div>
        <label>Tel&eacute;fono</label>
        <input type="text" name="telefono" value="<?php echo htmlspecialchars($fila["telefono"]); ?>" maxlength="10" required>
    </div>
</div>

<div class="accionesFormulario">
    <a class="botonSecundario" href="<?php echo urlLimpia("view/usuarios/listar.php"); ?>">Cancelar</a>
    <input type="submit" value="Actualizar usuario">
</div>
</form>
</main>
</body>
</html>

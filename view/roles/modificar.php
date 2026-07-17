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
$idRol = (int) ($_GET["id"] ?? 0);
$resultado = $idRol > 0 ? $modelo->buscarPorId($idRol) : false;
$fila = $resultado ? $resultado->fetch_assoc() : null;

if(!$fila){
    header("Location: " . urlLimpia("view/roles/listar.php?mensaje=error"));
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/roles/">
<title>Modificar rol &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Edici&oacute;n</p>
        <h2>Modificar rol</h2>
        <p class="descripcionSeccion">Actualiza el nombre y la descripci&oacute;n del rol seleccionado.</p>
    </div>
    <a class="botonSecundario" href="<?php echo urlLimpia("view/roles/listar.php"); ?>">Volver</a>
</div>

<form action="<?php echo urlLimpia("controller/rolController.php"); ?>" method="POST" class="formulario formularioPanel">
<input type="hidden" name="accion" value="modificar">
<input type="hidden" name="idRol" value="<?php echo (int) $fila["idRol"]; ?>">

<label>Nombre del rol</label>
<input type="text" name="nombre" value="<?php echo htmlspecialchars($fila["nombre"]); ?>" maxlength="50" required>

<label>Descripci&oacute;n</label>
<textarea name="descripcion" rows="4" required><?php echo htmlspecialchars($fila["descripcion"]); ?></textarea>

<div class="accionesFormulario">
    <a class="botonSecundario" href="<?php echo urlLimpia("view/roles/listar.php"); ?>">Cancelar</a>
    <input type="submit" value="Actualizar rol">
</div>
</form>
</main>
</body>
</html>

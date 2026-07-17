<?php
session_start();
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/roles/">
<title>Crear rol &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Nuevo perfil</p>
        <h2>Crear rol</h2>
        <p class="descripcionSeccion">Define un rol y luego asigna sus permisos desde el listado.</p>
    </div>
    <a class="botonSecundario" href="<?php echo urlLimpia("view/roles/listar.php"); ?>">Volver</a>
</div>

<?php
if(isset($_GET["mensaje"])){
    if($_GET["mensaje"]=="ok"){
        echo '<div class="mensajeExito">Rol creado correctamente.</div>';
    }
    if($_GET["mensaje"]=="error"){
        echo '<div class="mensajeError">No se pudo crear el rol. Verifica que todos los campos est&eacute;n completos.</div>';
    }
}
?>

<form action="<?php echo urlLimpia("controller/rolController.php"); ?>" method="POST" class="formulario formularioPanel">
<input type="hidden" name="accion" value="guardar">

<label>Nombre del rol</label>
<input type="text" name="nombre" placeholder="Ej. Supervisor" maxlength="50" required>

<label>Descripci&oacute;n</label>
<textarea name="descripcion" rows="4" placeholder="Describe brevemente qu&eacute; puede hacer este rol" required></textarea>

<div class="accionesFormulario">
    <a class="botonSecundario" href="<?php echo urlLimpia("view/roles/listar.php"); ?>">Cancelar</a>
    <input type="submit" value="Guardar rol">
</div>
</form>
</main>
</body>
</html>

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
require_once(__DIR__ . "/../../model/modeloMenu.php");
$modelo = new Menu();
$fila = $modelo->obtenerPorId((int) ($_GET["id"] ?? 0));

if(!$fila){
    header("Location: " . urlLimpia("view/menus/listar.php"));
    exit();
}

$menusPadre = $modelo->obtenerMenusPadre();
$paginasDisponibles = require(__DIR__ . "/../../config/paginasDisponibles.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/menus/">
<title>Modificar men&uacute; &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Navegaci&oacute;n</p>
        <h2>Modificar opci&oacute;n de men&uacute;</h2>
        <p class="descripcionSeccion">Actualiza el nombre, destino y orden de la opci&oacute;n seleccionada.</p>
    </div>
    <a class="botonSecundario" href="<?php echo urlLimpia("view/menus/listar.php"); ?>">Volver</a>
</div>

<?php
if(isset($_GET["mensaje"]) && $_GET["mensaje"]=="error"){
    echo '<div class="mensajeError">Completa nombre y p&aacute;gina antes de guardar.</div>';
}
?>

<form action="<?php echo urlLimpia("controller/menuController.php"); ?>" method="POST" class="formulario formularioPanel" id="formMenu">
<input type="hidden" name="accion" value="actualizar">
<input type="hidden" name="idMenu" value="<?php echo (int) $fila["idMenu"]; ?>">

<label>Nombre visible en el men&uacute;</label>
<input type="text" name="nombre" value="<?php echo htmlspecialchars($fila["nombre"]); ?>" required>

<label>&iquest;A qu&eacute; men&uacute; pertenece?</label>
<select name="idMenuPadre" id="idMenuPadre">
    <option value="" <?php echo is_null($fila["idMenuPadre"]) ? "selected" : ""; ?>>
        Opci&oacute;n principal (nivel superior)
    </option>
    <?php while($padre = $menusPadre->fetch_assoc()){ ?>
        <?php if((int) $padre["idMenu"] === (int) $fila["idMenu"]) continue; ?>
    <option value="<?php echo (int) $padre["idMenu"]; ?>"
        <?php echo ((int) $fila["idMenuPadre"] === (int) $padre["idMenu"]) ? "selected" : ""; ?>>
        Submen&uacute; de: <?php echo htmlspecialchars($padre["nombre"]); ?>
    </option>
    <?php } ?>
</select>

<?php $esGrupoActual = is_null($fila["idMenuPadre"]) && $fila["url"] === "#"; ?>
<div id="bloqueEsGrupo" class="campoCheckbox">
    <label>
        <input type="checkbox" name="esGrupo" id="esGrupo" value="1" <?php echo $esGrupoActual ? "checked" : ""; ?>>
        Este men&uacute; solo agrupa submen&uacute;s y no lleva a una p&aacute;gina propia.
    </label>
</div>

<div id="bloqueUrl">
    <label>P&aacute;gina de destino</label>
    <select name="url" id="url" required>
        <option value="">Selecciona una p&aacute;gina</option>
        <?php foreach($paginasDisponibles as $etiqueta => $ruta){ ?>
        <option value="<?php echo htmlspecialchars($ruta); ?>"
            <?php echo ($fila["url"] === $ruta) ? "selected" : ""; ?>>
            <?php echo htmlspecialchars($etiqueta); ?>
        </option>
        <?php } ?>
    </select>
</div>

<label>Orden</label>
<input type="text" name="ordenMenu" value="<?php echo htmlspecialchars($fila["ordenMenu"]); ?>" required>

<div class="accionesFormulario">
    <a class="botonSecundario" href="<?php echo urlLimpia("view/menus/listar.php"); ?>">Cancelar</a>
    <input type="submit" value="Guardar cambios">
</div>
</form>
</main>

<script>
(function(){
    var selectPadre = document.getElementById("idMenuPadre");
    var bloqueEsGrupo = document.getElementById("bloqueEsGrupo");
    var checkEsGrupo = document.getElementById("esGrupo");
    var bloqueUrl = document.getElementById("bloqueUrl");
    var selectUrl = document.getElementById("url");

    function actualizar(){
        var esNivelSuperior = selectPadre.value === "";

        bloqueEsGrupo.style.display = esNivelSuperior ? "block" : "none";
        if(!esNivelSuperior){
            checkEsGrupo.checked = false;
        }

        var ocultarUrl = esNivelSuperior && checkEsGrupo.checked;
        bloqueUrl.style.display = ocultarUrl ? "none" : "block";
        selectUrl.required = !ocultarUrl;
    }

    selectPadre.addEventListener("change", actualizar);
    checkEsGrupo.addEventListener("change", actualizar);
    actualizar();
})();
</script>

</body>
</html>

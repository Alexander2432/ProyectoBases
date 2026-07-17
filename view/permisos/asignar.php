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
require_once(__DIR__ . "/../../model/modeloPermisos.php");

$rolModelo = new Rol();
$roles = $rolModelo->listar();
$listaRoles = [];
while($fila = $roles->fetch_assoc()){
    $listaRoles[] = $fila;
}

$idRolSeleccionado = isset($_GET["idRol"]) ? (int) $_GET["idRol"] : ($listaRoles[0]["idRol"] ?? 0);

$permisoModelo = new Permiso();
$resultado = [];
if($idRolSeleccionado){
    $filas = $permisoModelo->obtenerMenusConEstado($idRolSeleccionado);
    while($f = $filas->fetch_assoc()){
        $resultado[$f["idMenu"]] = $f;
        $resultado[$f["idMenu"]]["hijos"] = [];
    }
}

$raiz = [];
foreach($resultado as $idMenu => $menu){
    if($menu["idMenuPadre"] === null){
        $raiz[] = $idMenu;
    }else if(isset($resultado[$menu["idMenuPadre"]])){
        $resultado[$menu["idMenuPadre"]]["hijos"][] = $idMenu;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/permisos/">
<title>Permisos &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Seguridad</p>
        <h2>Asignar permisos por rol</h2>
        <p class="descripcionSeccion">Selecciona las opciones de men&uacute; que podr&aacute; ver el rol elegido.</p>
    </div>
</div>

<?php if(isset($_GET["mensaje"]) && $_GET["mensaje"]=="ok"){ ?>
    <div class="mensajeExito">Permisos actualizados correctamente.</div>
<?php } ?>
<?php if(isset($_GET["mensaje"]) && $_GET["mensaje"]=="no_permitido"){ ?>
    <div class="mensajeError">No se permite modificar los permisos del Administrador para evitar bloqueos del sistema.</div>
<?php } ?>

<form action="<?php echo urlLimpia("view/permisos/asignar.php"); ?>" method="GET" class="formulario formularioCompacto">
    <label>Selecciona un rol</label>
    <select name="idRol" onchange="this.form.submit()">
        <?php foreach($listaRoles as $rol){ ?>
        <option value="<?php echo (int) $rol["idRol"]; ?>" <?php echo $rol["idRol"]==$idRolSeleccionado ? "selected" : ""; ?>>
            <?php echo htmlspecialchars($rol["nombre"]); ?>
        </option>
        <?php } ?>
    </select>
</form>

<form action="<?php echo urlLimpia("controller/permisoController.php"); ?>" method="POST" class="formularioPanel panelPermisos">
    <input type="hidden" name="accion" value="guardar">
    <input type="hidden" name="idRol" value="<?php echo (int) $idRolSeleccionado; ?>">

    <div class="matrizPermisos">
    <?php foreach($raiz as $idPadre){
        $padre = $resultado[$idPadre];
    ?>
        <div class="grupoPermiso">
            <label>
                <input type="checkbox" name="idMenu[]" value="<?php echo (int) $idPadre; ?>" <?php echo $padre["asignado"] ? "checked" : ""; ?>>
                <?php echo htmlspecialchars($padre["nombre"]); ?>
            </label>
            <?php if(!empty($padre["hijos"])){ ?>
            <div class="subPermisos">
                <?php foreach($padre["hijos"] as $idHijo){
                    $hijo = $resultado[$idHijo];
                ?>
                <label class="itemPermiso">
                    <input type="checkbox" name="idMenu[]" value="<?php echo (int) $idHijo; ?>" <?php echo $hijo["asignado"] ? "checked" : ""; ?>>
                    <?php echo htmlspecialchars($hijo["nombre"]); ?>
                </label>
                <?php } ?>
            </div>
            <?php } ?>
        </div>
    <?php } ?>
    </div>

    <div class="accionesFormulario">
        <input type="submit" value="Guardar permisos">
    </div>
</form>

</main>
</body>
</html>

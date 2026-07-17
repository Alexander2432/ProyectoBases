<?php

session_start();

if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}

require_once(__DIR__ . "/../model/modeloPermisos.php");
require_once(__DIR__ . "/../config/auth.php");

requerirPermisoUrl("view/permisos/asignar.php");

$modelo = new Permiso();

if (isset($_POST["accion"]) && $_POST["accion"] == "guardar") {

    $idRol   = (int) $_POST["idRol"];
    $idsMenu = $_POST["idMenu"] ?? [];

    if ($idRol === 1) {
        header("Location: " . urlLimpia("view/permisos/asignar.php?idRol=$idRol&mensaje=no_permitido"));
        exit();
    }

    $modelo->guardarPermisos($idRol, $idsMenu);

    header("Location: " . urlLimpia("view/permisos/asignar.php?idRol=$idRol&mensaje=ok"));
    exit();

}

?>

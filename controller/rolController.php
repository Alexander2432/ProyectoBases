<?php

session_start();

if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}

require_once(__DIR__ . "/../model/modeloRoles.php");
require_once(__DIR__ . "/../config/auth.php");

$modelo = new Rol();

if (isset($_POST["accion"])) {

    if ($_POST["accion"] == "guardar") {
        requerirPermisoUrl("view/roles/create.php");

        $nombre      = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");

        if ($nombre === "" || $descripcion === "") {
            header("Location: " . urlLimpia("view/roles/create.php?mensaje=error"));
            exit();
        }

        $resultado = $modelo->guardar($nombre, $descripcion);

        header("Location: " . urlLimpia("view/roles/create.php?mensaje=" . ($resultado ? "ok" : "error")));
        exit();

    }

    if ($_POST["accion"] == "modificar") {
        requerirPermisoUrl("view/roles/listar.php");

        $idRol       = (int) ($_POST["idRol"] ?? 0);
        $nombre      = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");

        if ($idRol <= 0 || $nombre === "" || $descripcion === "") {
            header("Location: " . urlLimpia("view/roles/listar.php?mensaje=error"));
            exit();
        }

        $resultado = $modelo->modificar($idRol, $nombre, $descripcion);

        header("Location: " . urlLimpia("view/roles/listar.php?mensaje=" . ($resultado ? "modificado" : "error")));
        exit();

    }

}

if (isset($_GET["accion"]) && $_GET["accion"] == "eliminar") {
    requerirPermisoUrl("view/roles/listar.php");
    $idRol = (int) ($_GET["id"] ?? 0);
    
    if ($idRol === 1) {
        header("Location: " . urlLimpia("view/roles/listar.php?mensaje=admin_nodo"));
        exit();
    }
    
    $res = $modelo->eliminar($idRol);
    if ($res === "tiene_usuarios") {
        header("Location: " . urlLimpia("view/roles/listar.php?mensaje=tiene_usuarios"));
    } else if ($res) {
        header("Location: " . urlLimpia("view/roles/listar.php?mensaje=eliminado"));
    } else {
        header("Location: " . urlLimpia("view/roles/listar.php?mensaje=error"));
    }
    exit();
}

header("Location: " . urlLimpia("view/roles/listar.php"));
exit();

?>

<?php

session_start();

if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}

require_once(__DIR__ . "/../model/modeloMenu.php");
require_once(__DIR__ . "/../model/modeloPermisos.php");
require_once(__DIR__ . "/../config/auth.php");

requerirPermisoUrl("view/menus/listar.php");

$modelo         = new Menu();
$permisoModelo  = new Permiso();
const ID_ROL_ADMINISTRADOR = 1;
$paginasValidas = require(__DIR__ . "/../config/paginasDisponibles.php");

if (isset($_POST["accion"])) {

    $accion = $_POST["accion"];

    if ($accion == "agregarPagina") {
        $etiqueta = trim($_POST["etiqueta"] ?? "");
        $ruta = trim($_POST["ruta"] ?? "");
        if ($etiqueta !== "" && $ruta !== "") {
            $filePath = __DIR__ . "/../config/paginasDisponibles.php";
            $paginas = require($filePath);
            $paginas[$etiqueta] = $ruta;
            $content = "<?php\nreturn " . var_export($paginas, true) . ";\n";
            file_put_contents($filePath, $content);
            header('Content-Type: application/json');
            echo json_encode(["success" => true, "etiqueta" => $etiqueta, "ruta" => $ruta]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(["success" => false, "mensaje" => "Datos inválidos"]);
        }
        exit();
    }

    if ($accion == "cambiarEstado") {

        $idMenu = (int) ($_POST["idMenu"] ?? 0);
        $activo = ((int) ($_POST["activo"] ?? 0)) === 1;

        $modelo->cambiarEstado($idMenu, $activo);

        header("Location: " . urlLimpia("view/menus/listar.php?mensaje=ok"));
        exit();

    }

    if ($accion == "eliminar") {

        $idMenu = (int) ($_POST["idMenu"] ?? 0);
        if ($modelo->tieneSubmenus($idMenu)) {
            header("Location: " . urlLimpia("view/menus/listar.php?mensaje=tieneSubmenus"));
            exit();
        }

                $permisoModelo->eliminarPorMenu($idMenu);
        $modelo->eliminar($idMenu);

        header("Location: " . urlLimpia("view/menus/listar.php?mensaje=eliminado"));
        exit();

    }

        $nombre      = trim($_POST["nombre"] ?? "");
    $ordenMenu   = (int) ($_POST["ordenMenu"] ?? 0);
    $idMenu      = (int) ($_POST["idMenu"] ?? 0);
    $idMenuPadre = trim($_POST["idMenuPadre"] ?? "");
    $idMenuPadre = ($idMenuPadre === "") ? null : (int) $idMenuPadre;
    $esGrupo = ($idMenuPadre === null) && isset($_POST["esGrupo"]) && $_POST["esGrupo"] === "1";

    if ($esGrupo) {
        $url = "#";
        $urlEsValida = true;
    } else {
        $url = trim($_POST["url"] ?? "");
        $urlEsValida = in_array($url, $paginasValidas, true);
    }

    if ($nombre === "" || !$urlEsValida) {

        if ($accion == "actualizar") {
            header("Location: " . urlLimpia("view/menus/modificar.php?id=" . $idMenu . "&mensaje=error"));
        } else {
            header("Location: " . urlLimpia("view/menus/create.php?mensaje=error"));
        }
        exit();

    }

    if ($accion == "guardar") {

        $idMenuNuevo = $modelo->crear($nombre, $url, $ordenMenu, $idMenuPadre);

        $permisoModelo->asignarPermiso(ID_ROL_ADMINISTRADOR, $idMenuNuevo);

        header("Location: " . urlLimpia("view/menus/create.php?mensaje=ok"));
        exit();

    } else if ($accion == "actualizar") {

        $modelo->actualizar($idMenu, $nombre, $url, $ordenMenu, $idMenuPadre);

        header("Location: " . urlLimpia("view/menus/listar.php?mensaje=ok"));
        exit();

    }

}

header("Location: " . urlLimpia("view/menus/listar.php"));
exit();

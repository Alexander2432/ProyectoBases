<?php

require_once(__DIR__ . "/conexion.php");
require_once(__DIR__ . "/constantes.php");

function iniciarSesionSiHaceFalta(){
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function requerirSesion($rutaLogin = null){
    iniciarSesionSiHaceFalta();

    if (!isset($_SESSION["idUsuario"])) {
        header("Location: " . ($rutaLogin ?? urlLimpia("view/index.html")));
        exit();
    }
}

function usuarioTienePermisoUrl($url){
    iniciarSesionSiHaceFalta();

    if (!isset($_SESSION["idRol"])) {
        return false;
    }

    if ($url === "" || $url === "#") {
        return true;
    }

    $url = rutaInternaDesdeRutaLimpia($url);

    $conexion = new Conexion();
    $cn = $conexion->conectar();

    $sql = "
        SELECT COUNT(*) AS total
        FROM permisos p
        INNER JOIN menus m ON m.idMenu = p.idMenu
        WHERE p.idRol = ?
        AND m.url = ?
        AND m.activo = 1
    ";

    $stmt = $cn->prepare($sql);
    $stmt->execute([$_SESSION["idRol"], $url]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $fila = array_change_key_case($fila, CASE_LOWER);
    }

    return $fila ? ((int) $fila["total"]) > 0 : false;
}

function requerirPermisoUrl($url){
    requerirSesion();

    if (!usuarioTienePermisoUrl($url)) {
        http_response_code(403);
        echo "<!DOCTYPE html><html lang=\"es\"><head><meta charset=\"UTF-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\"><title>Acceso denegado</title><link rel=\"stylesheet\" href=\"" . BASE_URL . "estilos/dashboard.css\"></head><body><main class=\"contenido\"><div class=\"mensajeError\">No tienes permiso para acceder a esta secci&oacute;n.</div><a class=\"botonSecundario\" href=\"" . urlLimpia("view/dashboard.html") . "\">Volver al inicio</a></main></body></html>";
        exit();
    }
}

function usuarioPuedeVerEstadisticasAdministrativas(){
    iniciarSesionSiHaceFalta();

    if (!isset($_SESSION["idRol"])) {
        return false;
    }

    $conexion = new Conexion();
    $cn = $conexion->conectar();

    $menusAdministrativos = [
        "view/usuarios/listar.php",
        "view/usuarios/create.php",
        "view/roles/listar.php",
        "view/roles/create.php",
        "view/menus/listar.php",
        "view/menus/create.php",
        "view/permisos/asignar.php",
    ];

    $marcadores = implode(",", array_fill(0, count($menusAdministrativos), "?"));
    $parametros = array_merge([$_SESSION["idRol"]], $menusAdministrativos);

    $sql = "
        SELECT COUNT(*) AS total
        FROM permisos p
        INNER JOIN menus m ON m.idMenu = p.idMenu
        WHERE p.idRol = ?
        AND m.url IN ($marcadores)
        AND m.activo = 1
    ";

    $stmt = $cn->prepare($sql);
    $stmt->execute($parametros);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($fila) {
        $fila = array_change_key_case($fila, CASE_LOWER);
    }

    return $fila ? ((int) $fila["total"]) > 0 : false;
}

?>

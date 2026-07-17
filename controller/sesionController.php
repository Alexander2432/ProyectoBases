<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

if (!isset($_SESSION["idUsuario"])) {
    http_response_code(401);
    echo json_encode(["success" => false, "mensaje" => "No hay sesión activa."]);
    exit();
}

require_once(__DIR__ . "/../model/modeloMenu.php");
require_once(__DIR__ . "/../model/modeloRoles.php");
require_once(__DIR__ . "/../model/usuario.php");
require_once(__DIR__ . "/../config/constantes.php");
require_once(__DIR__ . "/../config/auth.php");

$menuModelo = new Menu();
$menus      = $menuModelo->obtenerArbolMenu($_SESSION["idRol"]);

function aplicarRutasLimpiasMenu($menus){
    return $menus;
}

$menus = aplicarRutasLimpiasMenu($menus);

$rolModelo = new Rol();
$roles     = $rolModelo->listar();
$nombreRol = "";
while ($r = $roles->fetch_assoc()) {
    if ((int) $r["idRol"] === (int) $_SESSION["idRol"]) {
        $nombreRol = $r["nombre"];
        break;
    }
}

$puedeVerEstadisticas = usuarioPuedeVerEstadisticasAdministrativas();

$totalActivos   = 0;
$totalInactivos = 0;

if ($puedeVerEstadisticas) {
    $usuarioModelo = new Usuario();
    $conteo        = $usuarioModelo->contarPorEstado();

    while ($fila = $conteo->fetch_assoc()) {
        if ($fila["estado"] === "Activo") {
            $totalActivos = (int) $fila["total"];
        } else {
            $totalInactivos = (int) $fila["total"];
        }
    }
}

echo json_encode([
    "success" => true,
    "usuario" => [
        "usuario"   => $_SESSION["usuario"],
        "nombres"   => $_SESSION["nombres"]   ?? "",
        "apellidos" => $_SESSION["apellidos"] ?? "",
        "idRol"     => $_SESSION["idRol"],
        "nombreRol" => $nombreRol,
    ],
    "menu" => $menus,
    "estadisticas" => [
        "puedeVerEstadisticas" => $puedeVerEstadisticas,
        "usuariosActivos"   => $totalActivos,
        "usuariosInactivos" => $totalInactivos,
    ],
    "baseUrl" => BASE_URL,
]);

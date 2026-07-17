<?php

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$raizProyecto = dirname(__DIR__);
$documentRoot = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
$raizProyectoNormalizada = str_replace('\\', '/', $raizProyecto);

$documentRootLower = strtolower($documentRoot);
$raizProyectoLower = strtolower($raizProyectoNormalizada);

if ($documentRootLower !== '' && strpos($raizProyectoLower, $documentRootLower) === 0) {
    $carpetaProyecto = substr($raizProyectoNormalizada, strlen($documentRoot));
} else {
    $carpetaProyecto = str_ireplace($documentRoot, '', $raizProyectoNormalizada);
}

$segmentosProyecto = array_filter(explode('/', trim($carpetaProyecto, '/')), 'strlen');
$carpetaProyecto = '/' . implode('/', array_map('rawurlencode', $segmentosProyecto)) . '/';

define("BASE_URL", $protocolo . $host . $carpetaProyecto);

const RUTAS_LIMPIAS = [
    "view/index.php" => "login",
    "view/index.html" => "login",
    "view/dashboard.php" => "dashboard",
    "view/dashboard.html" => "dashboard",
    "view/usuarios/listar.php" => "usuarios",
    "view/usuarios/create.php" => "usuarios/crear",
    "view/usuarios/crear.php" => "usuarios/crear",
    "view/usuarios/modificar.php" => "usuarios/modificar",
    "view/roles/listar.php" => "roles",
    "view/roles/create.php" => "roles/crear",
    "view/roles/modificar.php" => "roles/modificar",
    "view/menus/listar.php" => "menus",
    "view/menus/create.php" => "menus/crear",
    "view/menus/modificar.php" => "menus/modificar",
    "view/permisos/asignar.php" => "permisos",
    "view/clientes/listar.php" => "clientes",
    "view/productos/listar.php" => "productos",
    "view/compras/registrar.php" => "compras/registrar",
    "view/compras/listar.php" => "compras",
    "view/ventas/registrar.php" => "ventas/registrar",
    "view/ventas/listar.php" => "ventas",
    "view/reportes/dashboard.php" => "reportes",
    "salir" => "controller/logout.php",
    "captcha" => "controller/captchaController.php",
    "login/validar" => "controller/loginController.php",
    "sesion" => "controller/sesionController.php",
];

const RUTAS_INTERNAS_LIMPIAS = [
    "login"              => "view/index.html",
    "dashboard"          => "view/dashboard.html",
    "usuarios"           => "view/usuarios/listar.php",
    "usuarios/crear"     => "view/usuarios/create.php",
    "usuarios/modificar" => "view/usuarios/modificar.php",
    "roles"              => "view/roles/listar.php",
    "roles/crear"        => "view/roles/create.php",
    "roles/modificar"    => "view/roles/modificar.php",
    "menus"              => "view/menus/listar.php",
    "menus/crear"        => "view/menus/create.php",
    "menus/modificar"    => "view/menus/modificar.php",
    "permisos"           => "view/permisos/asignar.php",
    "clientes"           => "view/clientes/listar.php",
    "productos"          => "view/productos/listar.php",
    "compras/registrar"  => "view/compras/registrar.php",
    "compras"            => "view/compras/listar.php",
    "ventas/registrar"   => "view/ventas/registrar.php",
    "ventas"             => "view/ventas/listar.php",
    "reportes"           => "view/reportes/dashboard.php",
    "salir"              => "controller/logout.php",
    "captcha"            => "controller/captchaController.php",
    "login/validar"      => "controller/loginController.php",
    "sesion"             => "controller/sesionController.php",
    "view/index.php"     => "view/index.html",
    "view/dashboard.php" => "view/dashboard.html",
];

function rutaLimpia($ruta){
    $ruta = ltrim(str_replace("\\", "/", $ruta), "/");

    if ($ruta === "" || $ruta === "#") {
        return $ruta;
    }

    $partes = explode("?", $ruta, 2);
    $rutaSinQuery = $partes[0];
    $query = isset($partes[1]) ? "?" . $partes[1] : "";

    return (RUTAS_LIMPIAS[$rutaSinQuery] ?? $rutaSinQuery) . $query;
}

function urlLimpia($ruta){
    $ruta = ltrim(str_replace("\\", "/", $ruta), "/");

    if ($ruta === "#") {
        return "#";
    }

    $partes = explode("?", $ruta, 2);
    $rutaSinQuery = $partes[0];
    $query = isset($partes[1]) ? "?" . $partes[1] : "";

    if (isset(RUTAS_INTERNAS_LIMPIAS[$rutaSinQuery])) {
        return BASE_URL . RUTAS_INTERNAS_LIMPIAS[$rutaSinQuery] . $query;
    }

    return BASE_URL . $rutaSinQuery . $query;
}

function rutaInternaDesdeRutaLimpia($ruta){
    $ruta = trim(str_replace("\\", "/", $ruta), "/");

    return RUTAS_INTERNAS_LIMPIAS[$ruta] ?? $ruta;
}

define("MAX_INTENTOS_FALLIDOS", 5);
define("MINUTOS_BLOQUEO", 5);

?>

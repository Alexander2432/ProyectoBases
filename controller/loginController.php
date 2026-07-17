<?php

session_start();
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

require_once(__DIR__ . "/../model/usuario.php");
require_once(__DIR__ . "/../config/constantes.php");

function responder($success, $mensaje, $campo = null){
    echo json_encode([
        "success" => $success,
        "mensaje" => $mensaje,
        "campo"   => $campo,
    ]);
    exit();
}

$datos = json_decode(file_get_contents("php://input"), true);
if (!is_array($datos)) {
    $datos = $_POST;
}

$usuario  = trim($datos["usuario"]  ?? "");
$password = trim($datos["password"] ?? "");
$captcha  = trim($datos["captcha"]  ?? "");
$ip       = $_SERVER["REMOTE_ADDR"] ?? "desconocida";

if ($usuario === "" || $password === "") {
    responder(false, "Ingrese usuario y contraseña.", "form");
}

if (!isset($_SESSION["captcha_resultado"])) {
    responder(false, "El captcha ha expirado, actualícelo.", "captcha");
}

if ($captcha === "" || strtoupper($captcha) !== strtoupper($_SESSION["captcha_resultado"])) {
    unset($_SESSION["captcha_resultado"]);
    responder(false, "El código de verificación es incorrecto.", "captcha");
}

unset($_SESSION["captcha_resultado"]);

$modelo    = new Usuario();
$resultado = $modelo->login($usuario);

if ($resultado->num_rows !== 1) {
    responder(false, "Usuario o contraseña incorrectos.", "usuario,password");
}

$fila = $resultado->fetch_assoc();

if (!password_verify($password, $fila["password"])) {
    $modelo->registrarBitacora($fila["idUsuario"], "Intento de acceso fallido", $ip);
    responder(false, "Usuario o contraseña incorrectos.", "usuario,password");
}

$modelo->registrarBitacora($fila["idUsuario"], "Inicio de sesión", $ip);

session_regenerate_id(true);

$_SESSION["idUsuario"] = $fila["idUsuario"];
$_SESSION["usuario"]   = $fila["usuario"];
$_SESSION["nombres"]   = $fila["nombres"];
$_SESSION["apellidos"] = $fila["apellidos"];
$_SESSION["idRol"]     = $fila["idRol"];

responder(true, "Bienvenido " . $fila["nombres"]);

<?php

session_start();

if (isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../model/usuario.php");
    $modelo = new Usuario();
    $ip = $_SERVER["REMOTE_ADDR"] ?? "desconocida";
    $modelo->registrarBitacora($_SESSION["idUsuario"], "Cierre de sesión", $ip);
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        "",
        time() - 3600,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );

}

session_destroy();

require_once(__DIR__ . "/../config/constantes.php");
header("Location: " . BASE_URL . "view/index.html");
exit();

?>

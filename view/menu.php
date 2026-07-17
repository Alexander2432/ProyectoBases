<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Thu, 01 Jan 1970 00:00:00 GMT");
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(__DIR__ . "/../model/modeloMenu.php");
require_once(__DIR__ . "/../config/constantes.php");
require_once(__DIR__ . "/../config/auth.php");

$rutaActual = str_replace('\\', '/', $_SERVER["SCRIPT_NAME"] ?? "");
$carpetaBase = parse_url(BASE_URL, PHP_URL_PATH) ?? "/";
$rutaRelativa = ltrim(str_replace(urldecode($carpetaBase), "", urldecode($rutaActual)), "/");
$rutaUrlActual = parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH) ?? "";
$rutaUrlRelativa = ltrim(str_replace(urldecode($carpetaBase), "", urldecode($rutaUrlActual)), "/");
$rutaUrlRelativa = rutaInternaDesdeRutaLimpia($rutaUrlRelativa);
if ($rutaUrlRelativa !== "") {
    $rutaRelativa = $rutaUrlRelativa;
}
$permisosEquivalentes = [
    "view/usuarios/modificar.php" => "view/usuarios/listar.php",
    "view/roles/modificar.php" => "view/roles/listar.php",
    "view/menus/modificar.php" => "view/menus/listar.php",
];
$rutaPermiso = $permisosEquivalentes[$rutaRelativa] ?? $rutaRelativa;

if ($rutaRelativa !== "" && $rutaRelativa !== "view/dashboard.html" && $rutaRelativa !== "view/dashboard.php") {
    requerirPermisoUrl($rutaPermiso);
}

$modelo = new Menu();
$menus = $modelo->obtenerMenuPrincipal($_SESSION["idRol"]);
?>
<nav>
<ul class="menu">
<?php while($menu = $menus->fetch_assoc()){ 
    $mUrl = trim($menu["url"] ?? "");
    $mLink = ($mUrl === "#" || $mUrl === "") ? "javascript:void(0)" : urlLimpia($mUrl);
?>
<li>
<a href="<?php echo $mLink; ?>">
<?php echo htmlspecialchars($menu["nombre"]); ?>
</a>
<?php
$submenus = $modelo->obtenerSubMenu($menu["idMenu"], $_SESSION["idRol"]);
if($submenus->num_rows > 0){
?>
<ul class="submenu">
<?php while($sub = $submenus->fetch_assoc()){ 
    $sUrl = trim($sub["url"] ?? "");
    $sLink = ($sUrl === "#" || $sUrl === "") ? "javascript:void(0)" : urlLimpia($sUrl);
?>
<li>
<a href="<?php echo $sLink; ?>">
<?php echo htmlspecialchars($sub["nombre"]); ?>
</a>
</li>
<?php } ?>
</ul>
<?php } ?>
</li>
<?php } ?>
<li class="logout">
    <a href="<?php echo urlLimpia("controller/logout.php"); ?>" class="btnCerrarSesion">
        Cerrar sesi&oacute;n
    </a>
</li>
</ul>
</nav>

<script>
if (window.parent && window.parent !== window) {
    if (!window.parent.sessionStorage.getItem("sesion_activa")) {
        window.location.href = "<?php echo urlLimpia('controller/logout.php'); ?>";
    } else {
        window.parent.sessionStorage.setItem("ultimo_url", window.location.href);
    }
}
</script>

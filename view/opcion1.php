<?php
session_start();
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../config/constantes.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/">
<title>Opción 1 · Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../estilos/dashboard.css">
</head>
<body>
<?php require_once(__DIR__ . "/menu.php"); ?>
<div class="contenido">
<h1>Opción 1</h1>
<p>Contenido de ejemplo para la opción 1, disponible para el rol correspondiente.</p>
</div>
</body>
</html>

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}

require_once(__DIR__ . "/../model/producto.php");
require_once(__DIR__ . "/../config/auth.php");

$modelo = new Producto();

requerirPermisoUrl("view/productos/listar.php");

if (isset($_POST["accion"])) {
    if ($_POST["accion"] == "guardar") {
        $codigo = trim($_POST["codigo"] ?? "");
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = (float)($_POST["precio"] ?? 0.00);
        $unidadMedida = trim($_POST["unidadMedida"] ?? "");
        $idCategoriaIva = (int)($_POST["idCategoriaIva"] ?? 0);
        $stockInicial = (float)($_POST["stock"] ?? 0.00);

        if ($modelo->existeCodigo($codigo)) {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=existe"));
            exit();
        }

        $resultado = $modelo->crear($codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva, $stockInicial);
        if ($resultado) {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=ok"));
        } else {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=error"));
        }
        exit();
    }

    if ($_POST["accion"] == "modificar") {
        $idProducto = (int)($_POST["idProducto"] ?? 0);
        $codigo = trim($_POST["codigo"] ?? "");
        $nombre = trim($_POST["nombre"] ?? "");
        $descripcion = trim($_POST["descripcion"] ?? "");
        $precio = (float)($_POST["precio"] ?? 0.00);
        $unidadMedida = trim($_POST["unidadMedida"] ?? "");
        $idCategoriaIva = (int)($_POST["idCategoriaIva"] ?? 0);

        if ($modelo->existeCodigo($codigo, $idProducto)) {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=existe"));
            exit();
        }

        $resultado = $modelo->actualizar($idProducto, $codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva);
        if ($resultado) {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=modificado"));
        } else {
            header("Location: " . urlLimpia("view/productos/listar.php?mensaje=error"));
        }
        exit();
    }
}

if (isset($_GET["accion"]) && $_GET["accion"] == "eliminar") {
    $idProducto = (int)($_GET["id"] ?? 0);
    $resultado = $modelo->eliminar($idProducto);
    if ($resultado) {
        header("Location: " . urlLimpia("view/productos/listar.php?mensaje=eliminado"));
    } else {
        header("Location: " . urlLimpia("view/productos/listar.php?mensaje=tiene_historico"));
    }
    exit();
}

// Retorna productos en formato JSON para autocompletar en compras/ventas
if (isset($_GET["accion"]) && $_GET["accion"] == "buscar_ajax") {
    header('Content-Type: application/json');
    $buscar = $_GET["q"] ?? "";
    $productos = $modelo->obtenerTodosPaginado(0, 10, $buscar);
    $salida = [];
    while ($producto = $productos->fetch_assoc()) {
        $salida[] = [
            'id' => $producto['idProducto'],
            'codigo' => $producto['codigo'],
            'nombre' => $producto['nombre'],
            'descripcion' => $producto['descripcion'],
            'precio' => (float)$producto['precio'],
            'unidadMedida' => $producto['unidadMedida'],
            'stock' => (float)$producto['stock'],
            'idCategoriaIva' => $producto['idCategoriaIva'],
            'ivaPorcentaje' => (float)$producto['ivaPorcentaje']
        ];
    }
    echo json_encode($salida);
    exit();
}
?>

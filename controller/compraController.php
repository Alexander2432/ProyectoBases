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

require_once(__DIR__ . "/../model/compra.php");
require_once(__DIR__ . "/../config/auth.php");

$modelo = new Compra();

requerirPermisoUrl("view/compras/registrar.php");

// Retorna detalles de la compra para visualizar en modal
if (isset($_GET["accion"]) && $_GET["accion"] == "detalle") {
    header('Content-Type: application/json');
    $idCompra = (int)($_GET["id"] ?? 0);
    $compra = $modelo->obtenerPorId($idCompra);
    if ($compra) {
        echo json_encode(['success' => true, 'compra' => $compra]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Compra no encontrada']);
    }
    exit();
}

// Procesa el JSON enviado por AJAX para registrar la compra
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    $entrada = file_get_contents('php://input');
    $datos = json_decode($entrada, true);
    
    if (!$datos || !isset($datos["productos"]) || empty($datos["productos"])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']);
        exit();
    }
    
    $idUsuario = $_SESSION["idUsuario"];
    $productos = $datos["productos"];
    
    $subtotal = 0.00;
    $ivaTotal = 0.00;
    
    $productosProcesados = [];
    require_once(__DIR__ . "/../model/producto.php");
    $modeloProducto = new Producto();
    
    foreach ($productos as $articulo) {
        $idProd = (int)$articulo["idProducto"];
        $cantidad = (int)$articulo["cantidad"];
        $precio = (float)$articulo["precio"];
        
        if ($idProd <= 0 || $cantidad <= 0 || $precio <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cantidad o precio no válidos.']);
            exit();
        }
        
        $producto = $modeloProducto->obtenerPorId($idProd);
        if (!$producto) {
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado en el catálogo.']);
            exit();
        }
        
        $subtotalArticulo = $cantidad * $precio;
        $ivaArticulo = $subtotalArticulo * ($producto['ivaPorcentaje'] / 100.00);
        $totalArticulo = $subtotalArticulo + $ivaArticulo;
        
        $subtotal += $subtotalArticulo;
        $ivaTotal += $ivaArticulo;
        
        $productosProcesados[] = [
            'idProducto' => $idProd,
            'cantidad' => $cantidad,
            'precio' => $precio,
            'iva' => $ivaArticulo,
            'total' => $totalArticulo
        ];
    }
    
    $total = $subtotal + $ivaTotal;
    
    $idCompra = $modelo->registrar($idUsuario, $productosProcesados, $subtotal, $ivaTotal, $total);
    if ($idCompra) {
        echo json_encode(['success' => true, 'message' => 'Compra registrada correctamente.', 'idCompra' => $idCompra]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error en la base de datos al registrar la compra.']);
    }
    exit();
}
?>

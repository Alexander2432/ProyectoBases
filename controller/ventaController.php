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

require_once(__DIR__ . "/../model/venta.php");
require_once(__DIR__ . "/../config/auth.php");

$modelo = new Venta();

requerirPermisoUrl("view/ventas/registrar.php");

// Retorna detalles de la factura de venta en JSON para ver en modal
if (isset($_GET["accion"]) && $_GET["accion"] == "detalle") {
    header('Content-Type: application/json');
    $idVenta = (int)($_GET["id"] ?? 0);
    $venta = $modelo->obtenerPorId($idVenta);
    if ($venta) {
        echo json_encode(['success' => true, 'venta' => $venta]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Venta no encontrada']);
    }
    exit();
}

// Genera y descarga el PDF de la factura
if (isset($_GET["accion"]) && $_GET["accion"] == "descargar_pdf") {
    $idVenta = (int)($_GET["id"] ?? 0);
    $venta = $modelo->obtenerPorId($idVenta);
    if ($venta) {
        require_once(__DIR__ . "/comprobanteGenerador.php");
        ComprobanteGenerador::generarPDF($venta);
    } else {
        echo "Venta no encontrada.";
    }
    exit();
}

// Genera y descarga el XML de la factura en formato SRI
if (isset($_GET["accion"]) && $_GET["accion"] == "descargar_xml") {
    $idVenta = (int)($_GET["id"] ?? 0);
    $venta = $modelo->obtenerPorId($idVenta);
    if ($venta) {
        require_once(__DIR__ . "/comprobanteGenerador.php");
        $xmlContent = ComprobanteGenerador::generarXML($venta);
        
        header('Content-Type: text/xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="Factura_' . $venta['numeroFactura'] . '.xml"');
        echo $xmlContent;
    } else {
        echo "Venta no encontrada.";
    }
    exit();
}

// Procesa el JSON enviado por AJAX para registrar la venta y facturar
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json');
    $entrada = file_get_contents('php://input');
    $datos = json_decode($entrada, true);
    
    if (!$datos || !isset($datos["idCliente"]) || !isset($datos["productos"]) || empty($datos["productos"])) {
        echo json_encode(['success' => false, 'message' => 'Datos incompletos o inválidos.']);
        exit();
    }
    
    $idCliente = (int)$datos["idCliente"];
    $idUsuario = $_SESSION["idUsuario"];
    $productos = $datos["productos"];
    
    if ($idCliente <= 0) {
        echo json_encode(['success' => false, 'message' => 'Debe seleccionar un cliente válido.']);
        exit();
    }
    
    require_once(__DIR__ . "/../model/cliente.php");
    $modeloCliente = new Cliente();
    $cliente = $modeloCliente->obtenerPorId($idCliente);
    if (!$cliente) {
        echo json_encode(['success' => false, 'message' => 'Cliente no encontrado.']);
        exit();
    }
    
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
            echo json_encode(['success' => false, 'message' => 'Producto no encontrado.']);
            exit();
        }
        
        // Valida stock disponible
        if ($producto['stock'] < $cantidad) {
            echo json_encode([
                'success' => false, 
                'message' => 'Stock insuficiente para "' . $producto['nombre'] . '". Stock actual: ' . $producto['stock'] . ' ' . $producto['unidadMedida']
            ]);
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
    
    $resultado = $modelo->registrar($idCliente, $idUsuario, $productosProcesados, $subtotal, $ivaTotal, $total);
    if (is_numeric($resultado)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Venta/Factura registrada con éxito.', 
            'idVenta' => $resultado,
            'numeroFactura' => $modelo->obtenerPorId($resultado)['numeroFactura'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => $resultado ? $resultado : 'Error al registrar la venta.']);
    }
    exit();
}
?>

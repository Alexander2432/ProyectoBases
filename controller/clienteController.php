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

require_once(__DIR__ . "/../model/cliente.php");
require_once(__DIR__ . "/../config/auth.php");

$modelo = new Cliente();

requerirPermisoUrl("view/clientes/listar.php");

// Algoritmo para verificar cedula o RUC ecuatoriano de 10 o 13 digitos
function validarIdentificacion($identificacion) {
    $identificacion = trim($identificacion);
    if (!preg_match('/^[0-9]{10}$/', $identificacion) && !preg_match('/^[0-9]{13}$/', $identificacion)) {
        return false;
    }

    $cedula = substr($identificacion, 0, 10);
    $provincia = (int) substr($cedula, 0, 2);
    $tercerDigito = (int) $cedula[2];
    $provinciaValida = ($provincia >= 1 && $provincia <= 24) || $provincia === 30;

    if (!$provinciaValida || $tercerDigito > 5) {
        return false;
    }

    $suma = 0;
    for ($i = 0; $i < 9; $i++) {
        $digito = (int) $cedula[$i];
        if ($i % 2 === 0) {
            $digito *= 2;
            if ($digito > 9) {
                $digito -= 9;
            }
        }
        $suma += $digito;
    }

    $verificador = (10 - ($suma % 10)) % 10;
    $cedulaValida = ($verificador === (int) $cedula[9]);

    if (strlen($identificacion) === 13) {
        return $cedulaValida && (substr($identificacion, 10, 3) === "001");
    }

    return $cedulaValida;
}

if (isset($_POST["accion"])) {
    if ($_POST["accion"] == "guardar") {
        $cedula = trim($_POST["cedula"] ?? "");
        $nombres = trim($_POST["nombres"] ?? "");
        $apellidos = trim($_POST["apellidos"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        if (!validarIdentificacion($cedula)) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=cedula_invalida"));
            exit();
        }

        if ($modelo->existeCedula($cedula)) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=existe"));
            exit();
        }

        $resultado = $modelo->crear($cedula, $nombres, $apellidos, $correo, $telefono, $direccion);
        if ($resultado) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=ok"));
        } else {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=error"));
        }
        exit();
    }

    if ($_POST["accion"] == "modificar") {
        $idCliente = (int)($_POST["idCliente"] ?? 0);
        $cedula = trim($_POST["cedula"] ?? "");
        $nombres = trim($_POST["nombres"] ?? "");
        $apellidos = trim($_POST["apellidos"] ?? "");
        $correo = trim($_POST["correo"] ?? "");
        $telefono = trim($_POST["telefono"] ?? "");
        $direccion = trim($_POST["direccion"] ?? "");

        if (!validarIdentificacion($cedula)) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=cedula_invalida"));
            exit();
        }

        if ($modelo->existeCedula($cedula, $idCliente)) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=existe"));
            exit();
        }

        $resultado = $modelo->actualizar($idCliente, $cedula, $nombres, $apellidos, $correo, $telefono, $direccion);
        if ($resultado) {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=modificado"));
        } else {
            header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=error"));
        }
        exit();
    }
}

if (isset($_GET["accion"]) && $_GET["accion"] == "eliminar") {
    $idCliente = (int)($_GET["id"] ?? 0);
    $resultado = $modelo->eliminar($idCliente);
    if ($resultado) {
        header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=eliminado"));
    } else {
        header("Location: " . urlLimpia("view/clientes/listar.php?mensaje=tiene_ventas"));
    }
    exit();
}

// Retorna lista de clientes en formato JSON para la busqueda en facturacion
if (isset($_GET["accion"]) && $_GET["accion"] == "buscar_ajax") {
    header('Content-Type: application/json');
    $buscar = $_GET["q"] ?? "";
    $clientes = $modelo->obtenerTodosPaginado(0, 10, $buscar);
    $salida = [];
    while ($cliente = $clientes->fetch_assoc()) {
        $salida[] = [
            'id' => $cliente['idCliente'],
            'cedula' => $cliente['cedula'],
            'nombre' => $cliente['nombres'] . ' ' . $cliente['apellidos'],
            'correo' => $cliente['correo'],
            'telefono' => $cliente['telefono'],
            'direccion' => $cliente['direccion']
        ];
    }
    echo json_encode($salida);
    exit();
}

// Retorna las ventas totales de un cliente usando procedimiento almacenado
if (isset($_GET["accion"]) && $_GET["accion"] == "ajax_ventas_totales") {
    header('Content-Type: application/json');
    require_once(__DIR__ . "/../model/venta.php");
    $idCliente = (int)($_GET["id"] ?? 0);
    $ventaModelo = new Venta();
    $total = $ventaModelo->obtenerVentasTotalesPorCliente($idCliente);
    echo json_encode(['success' => true, 'total' => $total]);
    exit();
}
?>

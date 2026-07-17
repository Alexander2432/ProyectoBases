<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../model/usuario.php");
require_once(__DIR__ . "/../config/auth.php");
$modelo = new Usuario();

if(isset($_POST["accion"]) && $_POST["accion"]=="guardar"){
    requerirPermisoUrl("view/usuarios/create.php");
}

if(
    (isset($_POST["accion"]) && $_POST["accion"]=="modificar")
    || (isset($_GET["accion"]) && $_GET["accion"]=="estado")
){
    requerirPermisoUrl("view/usuarios/listar.php");
}

const ID_ROL_ADMINISTRADOR = 1;
const ID_ROL_POR_DEFECTO   = 2;

function cedulaEcuatorianaValida($cedula){
    if(!preg_match('/^[0-9]{10}$/', $cedula)){
        return false;
    }

    $provincia = (int) substr($cedula, 0, 2);
    $tercerDigito = (int) $cedula[2];
    $provinciaValida = ($provincia >= 1 && $provincia <= 24) || $provincia === 30;

    if(!$provinciaValida || $tercerDigito > 5){
        return false;
    }

    $suma = 0;
    for($i = 0; $i < 9; $i++){
        $digito = (int) $cedula[$i];
        if($i % 2 === 0){
            $digito *= 2;
            if($digito > 9){
                $digito -= 9;
            }
        }
        $suma += $digito;
    }

    $verificador = (10 - ($suma % 10)) % 10;
    return $verificador === (int) $cedula[9];
}

if(isset($_POST["accion"])){
    if($_POST["accion"]=="guardar"){
        $usuario=$_POST["usuario"];
        $passwordRaw = $_POST["password"] ?? "";
        
        if(
            strlen($passwordRaw) < 8 ||
            !preg_match('/[A-Z]/', $passwordRaw) ||
            !preg_match('/[^a-zA-Z0-9]/', $passwordRaw)
        ) {
            header("Location: " . urlLimpia("view/usuarios/create.php?mensaje=password_invalida"));
            exit();
        }
        
        $password=password_hash($passwordRaw,PASSWORD_DEFAULT);
        $nombres=$_POST["nombres"];
        $apellidos=$_POST["apellidos"];
        $correo=$_POST["correo"];
        $telefono=$_POST["telefono"];
        $cedula=trim($_POST["cedula"] ?? "");

        if(!cedulaEcuatorianaValida($cedula)){
            header("Location: " . urlLimpia("view/usuarios/create.php?mensaje=cedula"));
            exit();
        }

        $idRol = (int) ($_POST["idRol"] ?? ID_ROL_POR_DEFECTO);
        if($idRol === ID_ROL_ADMINISTRADOR || $idRol <= 0){
            $idRol = ID_ROL_POR_DEFECTO;
        }

        $resultado=$modelo->guardar(
            $usuario, $password, $nombres, $apellidos, $correo, $telefono, $cedula, $idRol
        );

        if($resultado){
            header("Location: " . urlLimpia("view/usuarios/create.php?mensaje=ok"));
        }else{
            header("Location: " . urlLimpia("view/usuarios/create.php?mensaje=existe"));
        }
        exit();
    }

    if($_POST["accion"]=="modificar"){
        $idUsuario=(int)$_POST["idUsuario"];
        $nombres=$_POST["nombres"];
        $apellidos=$_POST["apellidos"];
        $correo=$_POST["correo"];
        $telefono=$_POST["telefono"];

        $existente = $modelo->buscarPorId($idUsuario)->fetch_assoc();
        if($existente){
            $usuario = $existente["usuario"];             $idRol = (int) ($_POST["idRol"] ?? ID_ROL_POR_DEFECTO);
            if($idRol <= 0){
                $idRol = ID_ROL_POR_DEFECTO;
            }
                        if((int)$existente["idRol"] === ID_ROL_ADMINISTRADOR || $existente["usuario"] === 'admin'){
                $idRol = ID_ROL_ADMINISTRADOR;
            }

            $modelo->modificar(
                $idUsuario,
                $usuario,
                $nombres,
                $apellidos,
                $correo,
                $telefono,
                $idRol
            );
        }
        header("Location: " . urlLimpia("view/usuarios/listar.php?mensaje=modificado"));
        exit();
    }
}
if(isset($_GET["accion"])){
    if($_GET["accion"]=="estado"){
        $idUsuario=$_GET["id"];
        if($idUsuario==$_SESSION["idUsuario"]){
            header("Location: " . urlLimpia("view/usuarios/listar.php?mensaje=propio"));
            exit();
        }
        $modelo->cambiarEstado($idUsuario);
        header("Location: " . urlLimpia("view/usuarios/listar.php?mensaje=estado"));
        exit();
    }
}

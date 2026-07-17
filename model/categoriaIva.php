<?php
require_once(__DIR__ . "/../config/conexion.php");

class CategoriaIva {
    public function obtenerTodos() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $sql = "SELECT * FROM categorias_iva ORDER BY porcentaje ASC";
        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function obtenerPorId($idCategoriaIva) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $stmt = $cn->prepare("SELECT * FROM categorias_iva WHERE idCategoriaIva = ?");
        $stmt->execute([$idCategoriaIva]);
        $res = new OracleResult($stmt);
        return $res->fetch_assoc();
    }
}
?>

<?php
require_once(__DIR__ . "/../config/conexion.php");

class Rol {
    public function listar() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT
                idRol,
                nombre,
                descripcion
            FROM roles
            ORDER BY idRol
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function listarTodos() {
        return $this->listar();
    }

    public function buscarPorId($idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT
                idRol,
                nombre,
                descripcion
            FROM roles
            WHERE idRol = ?
        ";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$idRol]);
        return new OracleResult($stmt);
    }

    public function guardar($nombre, $descripcion) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            INSERT INTO roles(nombre, descripcion)
            VALUES(?, ?)
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$nombre, $descripcion]);
    }

    public function modificar($idRol, $nombre, $descripcion) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            UPDATE roles
            SET
                nombre = ?,
                descripcion = ?
            WHERE idRol = ?
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$nombre, $descripcion, $idRol]);
    }

    public function listarSinAdministrador() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT idRol, nombre, descripcion
            FROM roles
            WHERE nombre <> 'Administrador'
            ORDER BY nombre
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function listarPaginado($offset, $limit) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $stmt = $cn->prepare("
            SELECT idRol, nombre, descripcion
            FROM roles
            ORDER BY idRol
            OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
        ");
        $stmt->bindValue(1, (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return new OracleResult($stmt);
    }

    public function contarTotal() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $res = $cn->query("SELECT COUNT(*) AS total FROM roles");
        $fila = $res->fetch(PDO::FETCH_ASSOC);
        return $fila ? (int)$fila["TOTAL"] : 0;
    }

    public function eliminar($idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM usuarios WHERE idRol = ?");
        $stmt->execute([$idRol]);
        $res = new OracleResult($stmt);
        $fila = $res->fetch_assoc();
        if ($fila && (int)$fila["total"] > 0) {
            return "tiene_usuarios";
        }
        
        $stmt2 = $cn->prepare("DELETE FROM permisos WHERE idRol = ?");
        $stmt2->execute([$idRol]);

        $stmt3 = $cn->prepare("DELETE FROM roles WHERE idRol = ?");
        return $stmt3->execute([$idRol]);
    }
}
?>

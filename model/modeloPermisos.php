<?php
require_once(__DIR__ . "/../config/conexion.php");

class Permiso {
    public function obtenerMenusConEstado($idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT
                m.idMenu,
                m.nombre,
                m.idMenuPadre,
                CASE WHEN p.idPermiso IS NOT NULL THEN 1 ELSE 0 END AS asignado
            FROM menus m
            LEFT JOIN permisos p ON p.idMenu = m.idMenu AND p.idRol = ?
            WHERE m.activo = 1
            ORDER BY CASE WHEN m.idMenuPadre IS NULL THEN 1 ELSE 0 END DESC, m.ordenMenu
        ";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$idRol]);
        return new OracleResult($stmt);
    }

    public function guardarPermisos($idRol, $idsMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $cn->beginTransaction();
        try {
            $borrar = $cn->prepare("DELETE FROM permisos WHERE idRol = ?");
            $borrar->execute([$idRol]);

            $insertar = $cn->prepare("INSERT INTO permisos (idRol, idMenu) VALUES (?, ?)");

            foreach ($idsMenu as $idMenu) {
                $idMenu = (int) $idMenu;
                $insertar->execute([$idRol, $idMenu]);
            }

            return $cn->commit();
        } catch (Exception $e) {
            $cn->rollBack();
            return false;
        }
    }

    public function asignarPermiso($idRol, $idMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $existe = $cn->prepare("SELECT idPermiso FROM permisos WHERE idRol = ? AND idMenu = ?");
        $existe->execute([$idRol, $idMenu]);
        $result = new OracleResult($existe);
        if ($result->num_rows > 0) return true;

        $insertar = $cn->prepare("INSERT INTO permisos (idRol, idMenu) VALUES (?, ?)");
        return $insertar->execute([$idRol, $idMenu]);
    }

    public function eliminarPorMenu($idMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $stmt = $cn->prepare("DELETE FROM permisos WHERE idMenu = ?");
        return $stmt->execute([$idMenu]);
    }
}
?>

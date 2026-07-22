<?php
require_once(__DIR__ . "/../config/conexion.php");

class Menu {
    public function obtenerMenuPrincipal($idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
        SELECT DISTINCT m.*
        FROM menus m
        INNER JOIN permisos p ON m.idMenu = p.idMenu
        WHERE p.idRol = ?
        AND m.idMenuPadre IS NULL
        AND m.activo = 1
        ORDER BY m.ordenMenu";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$idRol]);
        return new OracleResult($stmt);
    }

    public function obtenerSubMenu($idMenuPadre, $idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
        SELECT m.*
        FROM menus m
        INNER JOIN permisos p ON m.idMenu = p.idMenu
        WHERE m.idMenuPadre = ?
        AND p.idRol = ?
        AND m.activo = 1
        ORDER BY m.ordenMenu";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$idMenuPadre, $idRol]);
        return new OracleResult($stmt);
    }

    public function obtenerArbolMenu($idRol) {
        $principales = $this->obtenerMenuPrincipal($idRol);
        $arbol = [];

        while ($menu = $principales->fetch_assoc()) {
            $submenus = $this->obtenerSubMenu($menu["idMenu"], $idRol);
            $hijos = [];

            while ($sub = $submenus->fetch_assoc()) {
                $hijos[] = [
                    "nombre" => $sub["nombre"],
                    "url"    => $sub["url"],
                ];
            }

            $arbol[] = [
                "nombre"   => $menu["nombre"],
                "url"      => $menu["url"],
                "submenus" => $hijos,
            ];
        }

        return $arbol;
    }

    public function obtenerTodos() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT idMenu, nombre, url, ordenMenu, idMenuPadre, activo
            FROM menus
            ORDER BY CASE WHEN idMenuPadre IS NULL THEN 1 ELSE 0 END DESC, ordenMenu
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function obtenerMenusPadre() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT idMenu, nombre
            FROM menus
            WHERE idMenuPadre IS NULL
            AND activo = 1
            ORDER BY ordenMenu
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function cambiarEstado($idMenu, $activo) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $activo = $activo ? 1 : 0;
        $stmt = $cn->prepare("UPDATE menus SET activo = ? WHERE idMenu = ?");
        return $stmt->execute([$activo, $idMenu]);
    }

    public function obtenerPorId($idMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $stmt = $cn->prepare("SELECT * FROM menus WHERE idMenu = ?");
        $stmt->execute([$idMenu]);
        $res = new OracleResult($stmt);
        return $res->fetch_assoc();
    }

    public function crear($nombre, $url, $ordenMenu, $idMenuPadre) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            INSERT INTO menus (nombre, url, ordenMenu, idMenuPadre)
            VALUES (?, ?, ?, ?)
        ";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$nombre, $url, $ordenMenu, $idMenuPadre]);
        $stmtId = $cn->query("SELECT MAX(idMenu) AS id FROM menus");
        $rowId = $stmtId->fetch(PDO::FETCH_ASSOC);
        return (int)($rowId['ID'] ?? $rowId['id'] ?? $rowId['IDMENU'] ?? $rowId['idMenu']);
    }

    public function actualizar($idMenu, $nombre, $url, $ordenMenu, $idMenuPadre) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            UPDATE menus
            SET nombre = ?, url = ?, ordenMenu = ?, idMenuPadre = ?
            WHERE idMenu = ?
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$nombre, $url, $ordenMenu, $idMenuPadre, $idMenu]);
    }

    public function tieneSubmenus($idMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM menus WHERE idMenuPadre = ?");
        $stmt->execute([$idMenu]);
        $res = new OracleResult($stmt);
        $fila = $res->fetch_assoc();
        return $fila ? ((int)$fila["total"]) > 0 : false;
    }

    public function eliminar($idMenu) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $stmt = $cn->prepare("DELETE FROM menus WHERE idMenu = ?");
        return $stmt->execute([$idMenu]);
    }

    public function obtenerTodosPaginado($offset, $limit) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("
            SELECT idMenu, nombre, url, ordenMenu, idMenuPadre, activo
            FROM menus
            ORDER BY CASE WHEN idMenuPadre IS NULL THEN 1 ELSE 0 END DESC, ordenMenu
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
        $res = $cn->query("SELECT COUNT(*) AS total FROM menus");
        $fila = $res->fetch(PDO::FETCH_ASSOC);
        return $fila ? (int)$fila["TOTAL"] : 0;
    }
}
?>

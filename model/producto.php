<?php
require_once(__DIR__ . "/../config/conexion.php");

class Producto {
    public function obtenerTodos() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $sql = "SELECT p.*, c.nombre as categoriaIva, c.porcentaje as ivaPorcentaje 
                FROM productos p 
                INNER JOIN categorias_iva c ON p.idCategoriaIva = c.idCategoriaIva 
                ORDER BY p.nombre";
        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function obtenerTodosPaginado($offset, $limite, $buscar = "") {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($buscar !== "") {
            $buscarLike = "%" . $buscar . "%";
            $sql = "SELECT p.*, c.nombre as categoriaIva, c.porcentaje as ivaPorcentaje 
                    FROM productos p 
                    INNER JOIN categorias_iva c ON p.idCategoriaIva = c.idCategoriaIva 
                    WHERE p.codigo LIKE ? OR p.nombre LIKE ? OR p.descripcion LIKE ?
                    ORDER BY p.nombre
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(2, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(3, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(5, (int)$limite, PDO::PARAM_INT);
        } else {
            $sql = "SELECT p.*, c.nombre as categoriaIva, c.porcentaje as ivaPorcentaje 
                    FROM productos p 
                    INNER JOIN categorias_iva c ON p.idCategoriaIva = c.idCategoriaIva 
                    ORDER BY p.nombre
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limite, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return new OracleResult($stmt);
    }

    public function contarTotal($buscar = "") {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($buscar !== "") {
            $buscarLike = "%" . $buscar . "%";
            $sql = "SELECT COUNT(*) AS total FROM productos WHERE codigo LIKE ? OR nombre LIKE ? OR descripcion LIKE ?";
            $stmt = $cn->prepare($sql);
            $stmt->execute([$buscarLike, $buscarLike, $buscarLike]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $cn->query("SELECT COUNT(*) AS total FROM productos");
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }
        
        return $fila ? (int)$fila["total"] : 0;
    }

    public function obtenerPorId($idProducto) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $stmt = $cn->prepare("SELECT p.*, c.nombre as categoriaIva, c.porcentaje as ivaPorcentaje 
                              FROM productos p 
                              INNER JOIN categorias_iva c ON p.idCategoriaIva = c.idCategoriaIva 
                              WHERE p.idProducto = ?");
        $stmt->execute([$idProducto]);
        $result = new OracleResult($stmt);
        return $result->fetch_assoc();
    }

    public function obtenerPorCodigo($codigo) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $stmt = $cn->prepare("SELECT p.*, c.nombre as categoriaIva, c.porcentaje as ivaPorcentaje 
                              FROM productos p 
                              INNER JOIN categorias_iva c ON p.idCategoriaIva = c.idCategoriaIva 
                              WHERE p.codigo = ?");
        $stmt->execute([$codigo]);
        $result = new OracleResult($stmt);
        return $result->fetch_assoc();
    }

    public function crear($codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva, $stockInicial = 0.00) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("INSERT INTO productos (codigo, nombre, descripcion, precio, unidadMedida, idCategoriaIva, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([$codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva, $stockInicial]);
    }

    public function actualizar($idProducto, $codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("UPDATE productos SET codigo = ?, nombre = ?, descripcion = ?, precio = ?, unidadMedida = ?, idCategoriaIva = ? WHERE idProducto = ?");
        return $stmt->execute([$codigo, $nombre, $descripcion, $precio, $unidadMedida, $idCategoriaIva, $idProducto]);
    }

    public function eliminar($idProducto) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        // Verificar si tiene transacciones de compras o ventas asociadas
        $stmtCheck1 = $cn->prepare("SELECT COUNT(*) as total FROM detalle_compras WHERE idProducto = ?");
        $stmtCheck1->execute([$idProducto]);
        $res1 = $stmtCheck1->fetch(PDO::FETCH_ASSOC);
        if ($res1) {
            $res1 = array_change_key_case($res1, CASE_LOWER);
        }

        $stmtCheck2 = $cn->prepare("SELECT COUNT(*) as total FROM detalle_ventas WHERE idProducto = ?");
        $stmtCheck2->execute([$idProducto]);
        $res2 = $stmtCheck2->fetch(PDO::FETCH_ASSOC);
        if ($res2) {
            $res2 = array_change_key_case($res2, CASE_LOWER);
        }

        if ($res1 && $res2 && ((int)$res1["total"] > 0 || (int)$res2["total"] > 0)) {
            return false; // No se puede eliminar por integridad referencial
        }

        $stmt = $cn->prepare("DELETE FROM productos WHERE idProducto = ?");
        return $stmt->execute([$idProducto]);
    }

    public function existeCodigo($codigo, $idExcluir = 0) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($idExcluir > 0) {
            $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM productos WHERE codigo = ? AND idProducto != ?");
            $stmt->execute([$codigo, $idExcluir]);
        } else {
            $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM productos WHERE codigo = ?");
            $stmt->execute([$codigo]);
        }
        
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }
        return $fila ? ((int)$fila["total"]) > 0 : false;
    }

    public function actualizarStock($idProducto, $cantidad, $operacion = "sumar") {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($operacion === "sumar") {
            $stmt = $cn->prepare("UPDATE productos SET stock = stock + ? WHERE idProducto = ?");
        } else {
            $stmt = $cn->prepare("UPDATE productos SET stock = stock - ? WHERE idProducto = ?");
        }
        
        return $stmt->execute([$cantidad, $idProducto]);
    }
}
?>

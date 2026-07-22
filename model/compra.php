<?php
require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/producto.php");

class Compra {
    public function registrar($idUsuario, $productos, $subtotal, $iva, $total) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $cn->beginTransaction();
        
        try {
            // 1. Insertar en compras
            $stmt = $cn->prepare("INSERT INTO compras (idUsuario, subtotal, iva, total) VALUES (?, ?, ?, ?)");
            if (!$stmt->execute([$idUsuario, $subtotal, $iva, $total])) {
                throw new Exception("Error al insertar la cabecera de compra.");
            }
            $stmtId = $cn->query("SELECT MAX(idCompra) AS id FROM compras");
            $rowId = $stmtId->fetch(PDO::FETCH_ASSOC);
            $idCompra = (int)($rowId['ID'] ?? $rowId['id'] ?? $rowId['IDCOMPRA'] ?? $rowId['idCompra']);
            
            // 2. Insertar detalles y actualizar stock
            $stmtDetalle = $cn->prepare("INSERT INTO detalle_compras (idCompra, idProducto, cantidad, precio, iva, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtStock = $cn->prepare("UPDATE productos SET stock = stock + ? WHERE idProducto = ?");
            
            foreach ($productos as $p) {
                $idProductoVal = (int)$p['idProducto'];
                $cantidadVal = (int)$p['cantidad'];
                $precioVal = (float)$p['precio'];
                $ivaVal = (float)$p['iva'];
                $totalVal = (float)$p['total'];
                
                if (!$stmtDetalle->execute([$idCompra, $idProductoVal, $cantidadVal, $precioVal, $ivaVal, $totalVal])) {
                    throw new Exception("Error al insertar detalle de compra.");
                }
                
                if (!$stmtStock->execute([$cantidadVal, $idProductoVal])) {
                    throw new Exception("Error al actualizar stock del producto.");
                }
            }
            
            $cn->commit();
            return $idCompra;
            
        } catch (Throwable $e) {
            $cn->rollBack();
            error_log($e->getMessage());
            return false;
        }
    }

    public function obtenerTodosPaginado($offset, $limite, $idProducto = 0) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($idProducto > 0) {
            $sql = "SELECT c.*, u.usuario as encargado, dc.cantidad, dc.precio as precio_unitario, dc.iva as iva_item, dc.total as total_item, p.nombre as producto_nombre, p.codigo as producto_codigo
                    FROM compras c
                    INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
                    INNER JOIN detalle_compras dc ON c.idCompra = dc.idCompra
                    INNER JOIN productos p ON dc.idProducto = p.idProducto
                    WHERE p.idProducto = ?
                    ORDER BY c.fecha DESC
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, $idProducto, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$limite, PDO::PARAM_INT);
        } else {
            $sql = "SELECT c.*, u.usuario as encargado 
                    FROM compras c
                    INNER JOIN usuarios u ON c.idUsuario = u.idUsuario
                    ORDER BY c.fecha DESC
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$limite, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return new OracleResult($stmt);
    }

    public function contarTotal($idProducto = 0) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($idProducto > 0) {
            $sql = "SELECT COUNT(*) AS total 
                    FROM compras c
                    INNER JOIN detalle_compras dc ON c.idCompra = dc.idCompra
                    WHERE dc.idProducto = ?";
            $stmt = $cn->prepare($sql);
            $stmt->execute([$idProducto]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $res = $cn->query("SELECT COUNT(*) AS total FROM compras");
            $fila = $res->fetch(PDO::FETCH_ASSOC);
        }
        
        return $fila ? (int)$fila["TOTAL"] : 0;
    }

    public function obtenerPorId($idCompra) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("SELECT c.*, u.nombres as u_nombres, u.apellidos as u_apellidos, u.usuario as encargado 
                              FROM compras c 
                              INNER JOIN usuarios u ON c.idUsuario = u.idUsuario 
                              WHERE c.idCompra = ?");
        $stmt->execute([$idCompra]);
        
        $result = new OracleResult($stmt);
        $compra = $result->fetch_assoc();
        
        if ($compra) {
            $stmtDet = $cn->prepare("SELECT dc.*, p.nombre as producto_nombre, p.codigo as producto_codigo, p.unidadMedida 
                                     FROM detalle_compras dc 
                                     INNER JOIN productos p ON dc.idProducto = p.idProducto 
                                     WHERE dc.idCompra = ?");
            $stmtDet->execute([$idCompra]);
            $resultDet = new OracleResult($stmtDet);
            $compra['detalles'] = $resultDet->fetch_all();
        }
        
        return $compra;
    }
}
?>

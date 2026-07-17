<?php
require_once(__DIR__ . "/../config/conexion.php");
require_once(__DIR__ . "/producto.php");

class Venta {
    public function generarNumeroFactura() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $sql = "SELECT idVenta FROM (SELECT idVenta FROM ventas ORDER BY idVenta DESC) WHERE ROWNUM = 1";
        $res = $cn->query($sql);
        
        $secuencial = 1;
        if ($res) {
            $row = $res->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $secuencial = (int)$row['IDVENTA'] + 1;
            }
        }
        
        return sprintf("001-001-%09d", $secuencial);
    }

    public function registrar($idCliente, $idUsuario, $productos, $subtotal, $iva, $total) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $cn->beginTransaction();
        
        try {
            $numeroFactura = $this->generarNumeroFactura();
            
            // 1. Insertar en ventas
            $stmt = $cn->prepare("INSERT INTO ventas (numeroFactura, idCliente, idUsuario, subtotal, iva, total) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt->execute([$numeroFactura, $idCliente, $idUsuario, $subtotal, $iva, $total])) {
                throw new Exception("Error al insertar la cabecera de la venta.");
            }
            $idVenta = $cn->lastInsertId();
            
            // 2. Insertar detalles y actualizar stock
            $stmtDetalle = $cn->prepare("INSERT INTO detalle_ventas (idVenta, idProducto, cantidad, precio, iva, total) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtCheck = $cn->prepare("SELECT stock, nombre FROM productos WHERE idProducto = ?");
            $stmtStock = $cn->prepare("UPDATE productos SET stock = stock - ? WHERE idProducto = ?");
            
            foreach ($productos as $p) {
                $idProductoVal = (int)$p['idProducto'];
                $cantidadVal = (int)$p['cantidad'];
                $precioVal = (float)$p['precio'];
                $ivaVal = (float)$p['iva'];
                $totalVal = (float)$p['total'];
                
                $stmtCheck->execute([$idProductoVal]);
                $resCheck = $stmtCheck->fetch(PDO::FETCH_ASSOC);
                
                if (!$resCheck || $resCheck['STOCK'] < $cantidadVal) {
                    $nombreProd = $resCheck ? $resCheck['NOMBRE'] : 'Producto';
                    throw new Exception("Stock insuficiente para el producto: " . $nombreProd);
                }

                if (!$stmtDetalle->execute([$idVenta, $idProductoVal, $cantidadVal, $precioVal, $ivaVal, $totalVal])) {
                    throw new Exception("Error al insertar detalle de la venta.");
                }
                
                if (!$stmtStock->execute([$cantidadVal, $idProductoVal])) {
                    throw new Exception("Error al actualizar stock del producto.");
                }
            }
            
            $cn->commit();
            return $idVenta;
            
        } catch (Throwable $e) {
            $cn->rollBack();
            error_log($e->getMessage());
            return $e->getMessage();
        }
    }

    public function obtenerTodosPaginado($offset, $limite, $idProducto = 0) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($idProducto > 0) {
            $sql = "SELECT v.*, c.nombres as cliente_nombres, c.apellidos as cliente_apellidos, u.usuario as vendedor, dv.cantidad, dv.precio as precio_unitario, dv.iva as iva_item, dv.total as total_item, p.nombre as producto_nombre, p.codigo as producto_codigo
                    FROM ventas v
                    INNER JOIN clientes c ON v.idCliente = c.idCliente
                    INNER JOIN usuarios u ON v.idUsuario = u.idUsuario
                    INNER JOIN detalle_ventas dv ON v.idVenta = dv.idVenta
                    INNER JOIN productos p ON dv.idProducto = p.idProducto
                    WHERE p.idProducto = ?
                    ORDER BY v.fecha DESC
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, $idProducto, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(3, (int)$limite, PDO::PARAM_INT);
        } else {
            // Requerimiento: usar la vista V_REPORTES_VENTAS
            $sql = "SELECT * FROM V_REPORTES_VENTAS
                    ORDER BY fecha DESC
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
                    FROM ventas v
                    INNER JOIN detalle_ventas dv ON v.idVenta = dv.idVenta
                    WHERE dv.idProducto = ?";
            $stmt = $cn->prepare($sql);
            $stmt->execute([$idProducto]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $res = $cn->query("SELECT COUNT(*) AS total FROM ventas");
            $fila = $res->fetch(PDO::FETCH_ASSOC);
        }
        
        return $fila ? (int)$fila["TOTAL"] : 0;
    }

    public function obtenerPorId($idVenta) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("SELECT v.*, c.nombres as c_nombres, c.apellidos as c_apellidos, c.cedula as c_cedula, c.correo as c_correo, c.telefono as c_telefono, c.direccion as c_direccion, u.usuario as vendedor 
                              FROM ventas v 
                              INNER JOIN clientes c ON v.idCliente = c.idCliente
                              INNER JOIN usuarios u ON v.idUsuario = u.idUsuario 
                              WHERE v.idVenta = ?");
        $stmt->execute([$idVenta]);
        
        $result = new OracleResult($stmt);
        $venta = $result->fetch_assoc();
        
        if ($venta) {
            $stmtDet = $cn->prepare("SELECT dv.*, p.nombre as producto_nombre, p.codigo as producto_codigo, p.unidadMedida 
                                     FROM detalle_ventas dv 
                                     INNER JOIN productos p ON dv.idProducto = p.idProducto 
                                     WHERE dv.idVenta = ?");
            $stmtDet->execute([$idVenta]);
            $resultDet = new OracleResult($stmtDet);
            $venta['detalles'] = $resultDet->fetch_all();
        }
        
        return $venta;
    }

    // Procedimiento Almacenado Obligatorio: Ventas Totales por Cliente
    public function obtenerVentasTotalesPorCliente($idCliente) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("BEGIN PRC_VENTAS_TOTALES_CLIENTE(:idCliente, :total); END;");
        $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindParam(':total', $total, PDO::PARAM_STR, 100);
        $stmt->execute();
        
        return (float)$total;
    }

    // Procedimiento Almacenado Obligatorio: Ventas por Rango de Fechas
    public function obtenerVentasPorRangoFechas($fechaInicio, $fechaFin) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        // Se ejecuta una consulta directa a la vista para evitar la limitación de cursores de PDO OCI
        $stmt = $cn->prepare("SELECT * FROM V_REPORTES_VENTAS WHERE fecha BETWEEN ? AND ? ORDER BY fecha DESC");
        $stmt->execute([$fechaInicio, $fechaFin]);
        return new OracleResult($stmt);
    }
}
?>

<?php
require_once(__DIR__ . "/../config/conexion.php");

class Cliente {
    public function obtenerTodos() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $sql = "SELECT * FROM clientes ORDER BY apellidos, nombres";
        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function obtenerTodosPaginado($offset, $limite, $buscar = "") {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($buscar !== "") {
            $buscarLike = "%" . $buscar . "%";
            $sql = "SELECT * FROM clientes 
                    WHERE cedula LIKE ? OR nombres LIKE ? OR apellidos LIKE ? 
                    ORDER BY apellidos, nombres
                    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            $stmt = $cn->prepare($sql);
            $stmt->bindValue(1, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(2, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(3, $buscarLike, PDO::PARAM_STR);
            $stmt->bindValue(4, (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(5, (int)$limite, PDO::PARAM_INT);
        } else {
            $sql = "SELECT * FROM clientes ORDER BY apellidos, nombres
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
            $sql = "SELECT COUNT(*) AS total FROM clientes WHERE cedula LIKE ? OR nombres LIKE ? OR apellidos LIKE ?";
            $stmt = $cn->prepare($sql);
            $stmt->execute([$buscarLike, $buscarLike, $buscarLike]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $stmt = $cn->query("SELECT COUNT(*) AS total FROM clientes");
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }
        
        return $fila ? (int)$fila["total"] : 0;
    }

    public function obtenerPorId($idCliente) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $stmt = $cn->prepare("SELECT * FROM clientes WHERE idCliente = ?");
        $stmt->execute([$idCliente]);
        
        $result = new OracleResult($stmt);
        return $result->fetch_assoc();
    }

    public function obtenerPorCedula($cedula) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        $stmt = $cn->prepare("SELECT * FROM clientes WHERE cedula = ?");
        $stmt->execute([$cedula]);
        $result = new OracleResult($stmt);
        return $result->fetch_assoc();
    }

    public function crear($cedula, $nombres, $apellidos, $correo, $telefono, $direccion) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $operacion = 'C';
        $idCliente = 0;
        
        $stmt = $cn->prepare("BEGIN PRC_CRUD_CLIENTE(:operacion, :idCliente, :cedula, :nombres, :apellidos, :correo, :telefono, :direccion); END;");
        $stmt->bindParam(':operacion', $operacion, PDO::PARAM_STR);
        $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public function actualizar($idCliente, $cedula, $nombres, $apellidos, $correo, $telefono, $direccion) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        $operacion = 'U';
        
        $stmt = $cn->prepare("BEGIN PRC_CRUD_CLIENTE(:operacion, :idCliente, :cedula, :nombres, :apellidos, :correo, :telefono, :direccion); END;");
        $stmt->bindParam(':operacion', $operacion, PDO::PARAM_STR);
        $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':direccion', $direccion, PDO::PARAM_STR);
        
        return $stmt->execute();
    }

    public function eliminar($idCliente) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        // El trigger TRG_CLIENTES_BD de Oracle maneja la restricción de relación automáticamente.
        // Si el cliente tiene ventas asociadas, lanzará una excepción SQLException que capturamos.
        try {
            $operacion = 'D';
            $stmt = $cn->prepare("BEGIN PRC_CRUD_CLIENTE(:operacion, :idCliente); END;");
            $stmt->bindParam(':operacion', $operacion, PDO::PARAM_STR);
            $stmt->bindParam(':idCliente', $idCliente, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            // El trigger lanzó error de FK
            return false;
        }
    }

    public function existeCedula($cedula, $idExcluir = 0) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();
        
        if ($idExcluir > 0) {
            $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM clientes WHERE cedula = ? AND idCliente != ?");
            $stmt->execute([$cedula, $idExcluir]);
        } else {
            $stmt = $cn->prepare("SELECT COUNT(*) AS total FROM clientes WHERE cedula = ?");
            $stmt->execute([$cedula]);
        }
        
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }
        return $fila ? ((int)$fila["total"]) > 0 : false;
    }
}
?>

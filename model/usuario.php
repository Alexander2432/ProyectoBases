<?php

require_once(__DIR__ . "/../config/conexion.php");

class Usuario {

    public function login($usuario) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        // Enlazamos cada variable de salida de forma individual
        $stmt = $cn->prepare("BEGIN PRC_LOGIN_USUARIO(
            :p_usuario, :p_idUsuario, :p_password, :p_nombres, :p_apellidos, :p_correo, 
            :p_telefono, :p_cedula, :p_estado, :p_intentosFallidos, :p_bloqueadoHasta, 
            :p_idRol, :p_rol, :p_estaBloqueado, :p_segundosRestantes
        ); END;");

        $idUsuario = null;
        $password = null;
        $nombres = null;
        $apellidos = null;
        $correo = null;
        $telefono = null;
        $cedula = null;
        $estado = null;
        $intentosFallidos = null;
        $bloqueadoHasta = null;
        $idRol = null;
        $rol = null;
        $estaBloqueado = null;
        $segundosRestantes = null;

        $stmt->bindParam(':p_usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindParam(':p_idUsuario', $idUsuario, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':p_password', $password, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_nombres', $nombres, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_apellidos', $apellidos, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_correo', $correo, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_telefono', $telefono, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_cedula', $cedula, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_estado', $estado, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_intentosFallidos', $intentosFallidos, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':p_bloqueadoHasta', $bloqueadoHasta, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_idRol', $idRol, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':p_rol', $rol, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 500);
        $stmt->bindParam(':p_estaBloqueado', $estaBloqueado, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);
        $stmt->bindParam(':p_segundosRestantes', $segundosRestantes, PDO::PARAM_INT | PDO::PARAM_INPUT_OUTPUT, 100);

        $stmt->execute();

        if ($idUsuario) {
            $data = [[
                'idUsuario' => $idUsuario,
                'usuario' => $usuario,
                'password' => $password,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'correo' => $correo,
                'telefono' => $telefono,
                'cedula' => $cedula,
                'estado' => $estado,
                'intentosFallidos' => $intentosFallidos,
                'bloqueadoHasta' => $bloqueadoHasta,
                'idRol' => $idRol,
                'rol' => $rol,
                'estaBloqueado' => $estaBloqueado,
                'segundosRestantes' => $segundosRestantes
            ]];
        } else {
            $data = [];
        }

        return new OracleResult(null, $data);
    }

    public function registrarIntentoFallido($idUsuario, $maxIntentos, $minutosBloqueo) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        // Actualizamos intentos fallidos
        $stmt = $cn->prepare("UPDATE usuarios SET intentosFallidos = intentosFallidos + 1 WHERE idUsuario = ?");
        $stmt->execute([$idUsuario]);

        // Verificamos intentos
        $stmt = $cn->prepare("SELECT intentosFallidos FROM usuarios WHERE idUsuario = ?");
        $stmt->execute([$idUsuario]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }

        if ($fila && $fila["intentosfallidos"] >= $maxIntentos) {
            // Bloqueamos en base a minutosBloqueo
            $stmt = $cn->prepare("UPDATE usuarios SET bloqueadoHasta = SYSTIMESTAMP + NUMTODSINTERVAL(?, 'MINUTE') WHERE idUsuario = ?");
            $stmt->execute([$minutosBloqueo, $idUsuario]);
        }

        return $fila ? (int)$fila["intentosfallidos"] : 0;
    }

    public function reiniciarIntentos($idUsuario) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            UPDATE usuarios
            SET intentosFallidos = 0,
                bloqueadoHasta = NULL
            WHERE idUsuario = ?
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$idUsuario]);
    }

    public function registrarBitacora($idUsuario, $accion, $ip) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            INSERT INTO bitacora (idUsuario, accion, ip)
            VALUES (?, ?, ?)
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$idUsuario, $accion, $ip]);
    }

    public function guardar($usuario, $password, $nombres, $apellidos, $correo, $telefono, $cedula, $idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        // Llamamos al procedimiento almacenado obligatorio PRC_REGISTRO_USUARIO
        $stmt = $cn->prepare("BEGIN PRC_REGISTRO_USUARIO(:usuario, :password, :nombres, :apellidos, :correo, :telefono, :cedula, :idRol, :resultado); END;");
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindParam(':password', $password, PDO::PARAM_STR);
        $stmt->bindParam(':nombres', $nombres, PDO::PARAM_STR);
        $stmt->bindParam(':apellidos', $apellidos, PDO::PARAM_STR);
        $stmt->bindParam(':correo', $correo, PDO::PARAM_STR);
        $stmt->bindParam(':telefono', $telefono, PDO::PARAM_STR);
        $stmt->bindParam(':cedula', $cedula, PDO::PARAM_STR);
        $stmt->bindParam(':idRol', $idRol, PDO::PARAM_INT);
        $stmt->bindParam(':resultado', $resultado, PDO::PARAM_STR, 500);
        $stmt->execute();

        return $resultado;
    }

    public function listar() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT
                idUsuario,
                usuario,
                nombres,
                apellidos,
                correo,
                telefono,
                cedula,
                estado,
                u.idRol,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.idRol = u.idRol
            ORDER BY idUsuario
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function listarPaginado($offset, $limit) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        // Adaptado a la paginación de Oracle (OFFSET/FETCH)
        $stmt = $cn->prepare("
            SELECT
                idUsuario,
                usuario,
                nombres,
                apellidos,
                correo,
                telefono,
                cedula,
                estado,
                u.idRol,
                r.nombre AS rol
            FROM usuarios u
            INNER JOIN roles r ON r.idRol = u.idRol
            ORDER BY idUsuario
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
        $res = $cn->query("SELECT COUNT(*) AS total FROM usuarios");
        $fila = $res->fetch(PDO::FETCH_ASSOC);
        if ($fila) {
            $fila = array_change_key_case($fila, CASE_LOWER);
        }
        return $fila ? (int)$fila["total"] : 0;
    }

    public function contarPorEstado() {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT estado, COUNT(*) AS total
            FROM usuarios
            GROUP BY estado
        ";

        $stmt = $cn->query($sql);
        return new OracleResult($stmt);
    }

    public function buscarPorId($idUsuario) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            SELECT
                idUsuario,
                usuario,
                nombres,
                apellidos,
                correo,
                telefono,
                cedula,
                idRol
            FROM usuarios
            WHERE idUsuario = ?
        ";

        $stmt = $cn->prepare($sql);
        $stmt->execute([$idUsuario]);
        return new OracleResult($stmt);
    }

    public function modificar($idUsuario, $usuario, $nombres, $apellidos, $correo, $telefono, $idRol) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            UPDATE usuarios
            SET
                usuario = ?,
                nombres = ?,
                apellidos = ?,
                correo = ?,
                telefono = ?,
                idRol = ?
            WHERE idUsuario = ?
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$usuario, $nombres, $apellidos, $correo, $telefono, $idRol, $idUsuario]);
    }

    public function cambiarEstado($idUsuario) {
        $conexion = new Conexion();
        $cn = $conexion->conectar();

        $sql = "
            UPDATE usuarios
            SET estado =
                CASE
                    WHEN estado = 'Activo' THEN 'Inactivo'
                    ELSE 'Activo'
                END
            WHERE idUsuario = ?
        ";

        $stmt = $cn->prepare($sql);
        return $stmt->execute([$idUsuario]);
    }
}
?>

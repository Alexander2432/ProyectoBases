<?php

class Conexion
{
private $host = "localhost"; // O el host de tu base de datos si es diferente
private $puerto = "1521";    // El puerto configurado
private $servicio = "xe";    // Tu SID o Service Name (usualmente 'xe' o 'ORCL')
private $usuario = "system";  // El usuario con el que te conectaste (ej: system)
private $password = "NuevaClave123";   // La clave con la que ingresaste

    public function conectar()
    {
        try {
            // TNS Connection string para Oracle
            $tns = "(DESCRIPTION = 
                (ADDRESS = (PROTOCOL = TCP)(HOST = " . $this->host . ")(PORT = " . $this->puerto . "))
                (CONNECT_DATA = (SERVER = DEDICATED) (SERVICE_NAME = " . $this->servicio . "))
            )";
            
            // Creamos conexión usando PDO OCI
            $conexion = new PDO("oci:dbname=" . $tns . ";charset=UTF8", $this->usuario, $this->password);
            
            // Habilitamos el lanzamiento de excepciones para errores
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Forzar formato de números a punto decimal y formato de fechas estándar
            $conexion->exec("ALTER SESSION SET NLS_NUMERIC_CHARACTERS = '.,'");
            $conexion->exec("ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            $conexion->exec("ALTER SESSION SET NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            
            return $conexion;
        } catch (PDOException $e) {
            die("Error de conexión a Oracle: " . $e->getMessage());
        }
    }
}

class OracleResult
{
    private $rows = [];
    public $num_rows = 0;
    private $index = 0;

    public function __construct($stmt, $customRows = null)
    {
        if ($customRows !== null) {
            $this->rows = $customRows;
            $this->num_rows = count($customRows);
        } elseif ($stmt) {
            $raw_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $this->num_rows = count($raw_rows);
            foreach ($raw_rows as $row) {
                $this->rows[] = $this->normalizeKeys($row);
            }
        }
    }

    private function normalizeKeys($row)
    {
        $normalized = [];
        $mappings = [
            'idusuario' => 'idUsuario',
            'intentosfallidos' => 'intentosFallidos',
            'bloqueadohasta' => 'bloqueadoHasta',
            'idrol' => 'idRol',
            'fechanacimiento' => 'fechaNacimiento',
            'idmenu' => 'idMenu',
            'idmenupadre' => 'idMenuPadre',
            'ordenmenu' => 'ordenMenu',
            'idpermiso' => 'idPermiso',
            'idbitacora' => 'idBitacora',
            'idcliente' => 'idCliente',
            'idcategoriaiva' => 'idCategoriaIva',
            'idproducto' => 'idProducto',
            'idventa' => 'idVenta',
            'numerofactura' => 'numeroFactura',
            'iddetalleventa' => 'idDetalleVenta',
            'idcompra' => 'idCompra',
            'iddetallecompra' => 'idDetalleCompra',
            'establoqueado' => 'estaBloqueado',
            'segundosrestantes' => 'segundosRestantes',
            'categoriaiva' => 'categoriaIva',
            'ivaporcentaje' => 'ivaPorcentaje',
            'vendedor' => 'vendedor',
            'cliente_nombres' => 'cliente_nombres',
            'cliente_apellidos' => 'cliente_apellidos',
            'precio_unitario' => 'precio_unitario',
            'iva_item' => 'iva_item',
            'total_item' => 'total_item',
            'producto_nombre' => 'producto_nombre',
            'producto_codigo' => 'producto_codigo',
            'c_nombres' => 'c_nombres',
            'c_apellidos' => 'c_apellidos',
            'c_cedula' => 'c_cedula',
            'c_correo' => 'c_correo',
            'c_telefono' => 'c_telefono',
            'c_direccion' => 'c_direccion',
            'unidadmedida' => 'unidadMedida'
        ];
        
        foreach ($row as $key => $val) {
            $lowerKey = strtolower($key);
            $mappedKey = isset($mappings[$lowerKey]) ? $mappings[$lowerKey] : $lowerKey;
            $normalized[$mappedKey] = $val;
            $normalized[$lowerKey] = $val;
            $normalized[$key] = $val;
        }
        return $normalized;
    }

    public function fetch_assoc()
    {
        if ($this->index < $this->num_rows) {
            return $this->rows[$this->index++];
        }
        return null;
    }

    public function fetch_all($mode = null)
    {
        return $this->rows;
    }
}

?>

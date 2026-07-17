<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION["idUsuario"])) {
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
require_once(__DIR__ . "/../../model/producto.php");
require_once(__DIR__ . "/../../model/cliente.php");
require_once(__DIR__ . "/../../model/venta.php");
require_once(__DIR__ . "/../../model/usuario.php");

$modeloProducto = new Producto();
$todosProductos = $modeloProducto->obtenerTodos();
$productosJson = [];
while ($prod = $todosProductos->fetch_assoc()) {
    $productosJson[] = $prod;
}

$modeloCliente = new Cliente();
$todosClientes = $modeloCliente->obtenerTodos();
$clientesJson = [];
while ($cli = $todosClientes->fetch_assoc()) {
    $clientesJson[] = $cli;
}

$modeloUsuario = new Usuario();
$todosUsuarios = $modeloUsuario->listar();
$usuariosArray = [];
while ($usr = $todosUsuarios->fetch_assoc()) {
    if ($usr['estado'] === 'Activo') {
        $usuariosArray[] = $usr;
    }
}

$ventaModelo = new Venta();
$siguienteSecuencial = $ventaModelo->generarNumeroFactura();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/ventas/">
<title>Registrar Venta / Facturar</title>
<script>
    window.addEventListener('error', function(e) {
        const errDiv = document.createElement('div');
        errDiv.style.background = '#fee2e2';
        errDiv.style.color = '#991b1b';
        errDiv.style.padding = '15px';
        errDiv.style.border = '2px solid #f87171';
        errDiv.style.borderRadius = '8px';
        errDiv.style.margin = '20px';
        errDiv.style.fontWeight = 'bold';
        errDiv.style.fontSize = '14px';
        errDiv.innerHTML = '⚠️ ERROR DE JS DETECTADO: ' + e.message + '<br><small>Archivo: ' + e.filename + ' - Línea: ' + e.lineno + '</small>';
        document.body.insertBefore(errDiv, document.body.firstChild);
    });
</script>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
<style>
    .venta-contenedor {
        display: grid;
        grid-template-columns: 1fr 340px;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .venta-contenedor {
            grid-template-columns: 1fr;
        }
    }
    .panel-detalles {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: 20px;
    }
    .panel-resumen {
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--radius-md);
        padding: 20px;
        height: fit-content;
        position: sticky;
        top: 96px;
    }
    .grupo-campo {
        margin-bottom: 15px;
    }
    .grupo-campo label {
        display: block;
        margin-bottom: 5px;
        font-weight: 700;
        font-size: 13px;
        color: rgba(255,255,255,0.7);
    }
    .grupo-campo input, .grupo-campo select {
        width: 100%;
        padding: 10px;
        background: rgba(0,0,0,0.2);
        border: 1px solid var(--color-border);
        color: white;
        border-radius: var(--radius-sm);
        box-sizing: border-box;
    }
    .grupo-campo input[readonly] {
        background: rgba(255,255,255,0.05);
        color: rgba(255,255,255,0.6);
        cursor: not-allowed;
    }
    .seccion-clientes {
        border-bottom: 1px solid var(--color-border);
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    .fila-busqueda {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
        gap: 10px;
        margin-bottom: 20px;
        align-items: flex-end;
    }
    @media (max-width: 600px) {
        .fila-busqueda {
            grid-template-columns: 1fr;
        }
    }
    .totales-resumen {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid var(--color-border);
    }
    .total-fila {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 14px;
    }
    .total-fila.gran-total {
        font-size: 18px;
        font-weight: 800;
        color: var(--color-primary);
        margin-top: 10px;
        padding-top: 10px;
        border-top: 2px dashed var(--color-border);
    }
    .info-cliente-caja {
        background: rgba(255,255,255,0.03);
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 5px;
        padding: 10px;
        margin-top: 10px;
        font-size: 13px;
        display: none;
    }
    #btnProcesarVenta {
        background-color: #10b981 !important;
        color: #000000 !important;
        transition: all 0.2s ease;
    }
    #btnProcesarVenta:disabled {
        background-color: #374151 !important;
        color: #9ca3af !important;
        cursor: not-allowed !important;
        opacity: 0.5 !important;
    }
</style>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Transacciones</p>
        <h2>Facturación / Registrar Venta</h2>
        <p class="descripcionSeccion">Genera facturas a clientes. Reduce stock automáticamente y valida disponibilidad.</p>
    </div>
</div>

<div id="notificacion" style="display: none; margin-bottom: 20px;"></div>

<div class="venta-contenedor">
    <div class="panel-detalles">
        <!-- Selector de Cliente Simplificado -->
        <div class="seccion-clientes">
            <h3>Seleccionar Cliente</h3>
            <div class="grupo-campo">
                <select id="clienteSelect" onchange="seleccionarCliente()">
                    <option value="">-- Elige un cliente --</option>
                    <?php foreach ($clientesJson as $c) { ?>
                        <option value="<?php echo (int)$c['idCliente']; ?>">
                            <?php echo htmlspecialchars($c['cedula'] . ' - ' . $c['nombres'] . ' ' . $c['apellidos']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            
            <div id="infoCliente" class="info-cliente-caja">
                <p style="margin: 0 0 5px 0;"><strong>Cliente:</strong> <span id="cliNombre"></span></p>
                <p style="margin: 0 0 5px 0;"><strong>Identificación:</strong> <span id="cliCedula"></span></p>
                <p style="margin: 0;"><strong>Teléfono:</strong> <span id="cliTelefono"></span> | <strong>Dirección:</strong> <span id="cliDireccion"></span></p>
            </div>
        </div>
        
        <h3>Agregar Producto a la Factura</h3>
        <div class="fila-busqueda">
            <div>
                <label for="productoSelect">Producto</label>
                <select id="productoSelect" onchange="seleccionarProducto()">
                    <option value="">-- Elige un producto --</option>
                    <?php foreach ($productosJson as $p) { ?>
                        <option value="<?php echo (int)$p['idProducto']; ?>">
                            <?php echo htmlspecialchars($p['codigo'] . ' - ' . $p['nombre'] . ' ($' . number_format($p['precio'], 2) . ')'); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label>Stock Disponible</label>
                <input type="text" id="disponibleProducto" readonly placeholder="0.00">
            </div>
            <div>
                <label for="cantidadProducto">Cantidad</label>
                <input type="number" id="cantidadProducto" value="1" min="1" step="1">
            </div>
            <div>
                <button type="button" class="botonNuevo" id="btnAgregarItem" style="width: 100%; height: 42px;">Añadir</button>
            </div>
        </div>
        
        <div class="tablaPanel">
            <table>
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Precio U.</th>
                        <th>Subtotal</th>
                        <th>IVA</th>
                        <th>Total</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody id="cuerpoDetalle">
                    <tr>
                        <td colspan="8" style="text-align: center; color: rgba(255,255,255,0.4); padding: 30px 0;">
                            Ningún producto añadido todavía.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <div class="panel-resumen">
        <h3>Datos de Factura</h3>
        
        <div class="grupo-campo">
            <label>Número Factura</label>
            <input type="text" value="<?php echo htmlspecialchars($siguienteSecuencial); ?>" readonly style="color: var(--color-primary); font-weight: bold; text-align: center;">
        </div>
        
        <div class="grupo-campo">
            <label for="usuarioSelect">Vendedor</label>
            <select id="usuarioSelect">
                <?php foreach ($usuariosArray as $usr) { ?>
                    <option value="<?php echo (int)$usr['idUsuario']; ?>" <?php echo $usr['idUsuario'] == $_SESSION['idUsuario'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($usr['nombres'] . ' ' . $usr['apellidos']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        
        <div class="grupo-campo">
            <label>Fecha Emisión</label>
            <input type="text" id="fechaHora" readonly>
        </div>
        
        <div class="totales-resumen">
            <div class="total-fila">
                <span>Subtotal (Sin IVA):</span>
                <span id="resumenSubtotal">$0.00</span>
            </div>
            <div class="total-fila">
                <span>IVA:</span>
                <span id="resumenIva">$0.00</span>
            </div>
            <div class="total-fila gran-total">
                <span>TOTAL A PAGAR:</span>
                <span id="resumenTotal">$0.00</span>
            </div>
        </div>
        
        <button type="button" class="botonNuevo" id="btnProcesarVenta" style="width: 100%; margin-top: 20px; font-weight: 800;" disabled>
            Procesar Factura
        </button>
    </div>
</div>

</main>

<script>
    const listaCompletaProductos = <?php echo json_encode($productosJson); ?>;
    const listaCompletaClientes = <?php echo json_encode($clientesJson); ?>;
    
    let itemsVenta = [];
    let clienteSeleccionado = null;
    let productoSeleccionado = null;
    
    const clienteSelect = document.getElementById("clienteSelect");
    const infoCliente = document.getElementById("infoCliente");
    const cliNombre = document.getElementById("cliNombre");
    const cliCedula = document.getElementById("cliCedula");
    const cliTelefono = document.getElementById("cliTelefono");
    const cliDireccion = document.getElementById("cliDireccion");
    
    const productoSelect = document.getElementById("productoSelect");
    const disponibleProducto = document.getElementById("disponibleProducto");
    const cantidadProducto = document.getElementById("cantidadProducto");
    const btnAgregarItem = document.getElementById("btnAgregarItem");
    const cuerpoDetalle = document.getElementById("cuerpoDetalle");
    
    const resumenSubtotal = document.getElementById("resumenSubtotal");
    const resumenIva = document.getElementById("resumenIva");
    const resumenTotal = document.getElementById("resumenTotal");
    const btnProcesarVenta = document.getElementById("btnProcesarVenta");
    const notificacion = document.getElementById("notificacion");
    const usuarioSelect = document.getElementById("usuarioSelect");
    
    function actualizarFecha() {
        const ahora = new Date();
        document.getElementById("fechaHora").value = ahora.toLocaleString("es-EC");
    }
    actualizarFecha();
    
    function seleccionarCliente() {
        const id = parseInt(clienteSelect.value);
        if (isNaN(id)) {
            clienteSeleccionado = null;
            infoCliente.style.display = "none";
            validarFormularioListo();
            return;
        }
        
        clienteSeleccionado = listaCompletaClientes.find(c => parseInt(c.idCliente) === id);
        if (clienteSeleccionado) {
            cliNombre.textContent = clienteSeleccionado.nombres + ' ' + clienteSeleccionado.apellidos;
            cliCedula.textContent = clienteSeleccionado.cedula;
            cliTelefono.textContent = clienteSeleccionado.telefono || '-';
            cliDireccion.textContent = clienteSeleccionado.direccion || '-';
            infoCliente.style.display = "block";
        }
        validarFormularioListo();
    }
    
    function seleccionarProducto() {
        const id = parseInt(productoSelect.value);
        if (isNaN(id)) {
            productoSeleccionado = null;
            disponibleProducto.value = "";
            return;
        }
        
        productoSeleccionado = listaCompletaProductos.find(p => parseInt(p.idProducto) === id);
        if (productoSeleccionado) {
            disponibleProducto.value = `${parseFloat(productoSeleccionado.stock).toFixed(2)} ${productoSeleccionado.unidadMedida}`;
        }
    }
    
    btnAgregarItem.addEventListener("click", function() {
        if (!productoSeleccionado) {
            alert("Seleccione un producto de la lista.");
            return;
        }
        
        const cantidad = parseInt(cantidadProducto.value);
        if (isNaN(cantidad) || cantidad <= 0 || !Number.isInteger(cantidad)) {
            alert("Ingrese una cantidad entera válida mayor a cero.");
            return;
        }
        
        const index = itemsVenta.findIndex(item => item.idProducto === productoSeleccionado.idProducto);
        const cantPrevia = index !== -1 ? itemsVenta[index].cantidad : 0;
        
        if (parseFloat(productoSeleccionado.stock) < (cantPrevia + cantidad)) {
            alert(`Stock insuficiente. Solo quedan ${productoSeleccionado.stock} ${productoSeleccionado.unidadMedida} disponibles.`);
            return;
        }
        
        const subtotal = cantidad * parseFloat(productoSeleccionado.precio);
        const iva = subtotal * (parseFloat(productoSeleccionado.ivaPorcentaje) / 100);
        const total = subtotal + iva;
        
        if (index !== -1) {
            itemsVenta[index].cantidad += cantidad;
            itemsVenta[index].subtotal = itemsVenta[index].cantidad * itemsVenta[index].precio;
            itemsVenta[index].iva = itemsVenta[index].subtotal * (parseFloat(productoSeleccionado.ivaPorcentaje) / 100);
            itemsVenta[index].total = itemsVenta[index].subtotal + itemsVenta[index].iva;
        } else {
            itemsVenta.push({
                idProducto: productoSeleccionado.idProducto,
                codigo: productoSeleccionado.codigo,
                nombre: productoSeleccionado.nombre,
                ivaPorcentaje: parseFloat(productoSeleccionado.ivaPorcentaje),
                cantidad: cantidad,
                precio: parseFloat(productoSeleccionado.precio),
                subtotal: subtotal,
                iva: iva,
                total: total
            });
        }
        
        productoSelect.value = "";
        disponibleProducto.value = "";
        cantidadProducto.value = "1";
        productoSeleccionado = null;
        
        renderTabla();
    });
    
    function renderTabla() {
        if (itemsVenta.length === 0) {
            cuerpoDetalle.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; color: rgba(255,255,255,0.4); padding: 30px 0;">
                        Ningún producto añadido todavía.
                    </td>
                </tr>`;
            resumenSubtotal.textContent = "$0.00";
            resumenIva.textContent = "$0.00";
            resumenTotal.textContent = "$0.00";
            validarFormularioListo();
            return;
        }
        
        cuerpoDetalle.innerHTML = "";
        let subtotalAcumulado = 0;
        let ivaAcumulado = 0;
        
        itemsVenta.forEach((item, index) => {
            subtotalAcumulado += item.subtotal;
            ivaAcumulado += item.iva;
            
            const tr = document.createElement("tr");
            tr.innerHTML = `
                <td><strong>${item.codigo}</strong></td>
                <td>${item.nombre}</td>
                <td>${item.cantidad.toFixed(2)}</td>
                <td>$${item.precio.toFixed(4)}</td>
                <td>$${item.subtotal.toFixed(2)}</td>
                <td>$${item.iva.toFixed(2)} (${item.ivaPorcentaje}%)</td>
                <td><strong>$${item.total.toFixed(2)}</strong></td>
                <td><button type="button" class="accion accionPeligro" onclick="eliminarItem(${index})">Eliminar</button></td>
            `;
            cuerpoDetalle.appendChild(tr);
        });
        
        const totalGeneral = subtotalAcumulado + ivaAcumulado;
        
        resumenSubtotal.textContent = `$${subtotalAcumulado.toFixed(2)}`;
        resumenIva.textContent = `$${ivaAcumulado.toFixed(2)}`;
        resumenTotal.textContent = `$${totalGeneral.toFixed(2)}`;
        
        validarFormularioListo();
    }
    
    window.eliminarItem = function(index) {
        itemsVenta.splice(index, 1);
        renderTabla();
    };
    
    function validarFormularioListo() {
        if (clienteSeleccionado && itemsVenta.length > 0) {
            btnProcesarVenta.disabled = false;
        } else {
            btnProcesarVenta.disabled = true;
        }
    }
    
    btnProcesarVenta.addEventListener("click", async function() {
        if (!clienteSeleccionado || itemsVenta.length === 0) return;
        
        if (!confirm("¿Desea guardar la factura de venta? El stock se descontará de inmediato.")) {
            return;
        }
        
        btnProcesarVenta.disabled = true;
        
        try {
            const res = await fetch("<?php echo urlLimpia('controller/ventaController.php'); ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    idCliente: clienteSeleccionado.idCliente,
                    idUsuario: parseInt(usuarioSelect.value),
                    productos: itemsVenta
                })
            });
            const data = await res.json();
            
            if (data.success) {
                notificacion.className = "mensajeExito";
                notificacion.innerHTML = `
                    <strong>${data.message}</strong> Factura Nro: ${data.numeroFactura}
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <a href="../../controller/ventaController.php?accion=descargar_pdf&id=${data.idVenta}" target="_blank" style="background: #dc2626; color: white; padding: 8px 14px; border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer;">Imprimir PDF</a>
                        <a href="../../controller/ventaController.php?accion=descargar_xml&id=${data.idVenta}" download style="background: #2563eb; color: white; padding: 8px 14px; border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; border: none; cursor: pointer;">Descargar XML SRI</a>
                    </div>
                `;
                notificacion.style.display = "block";
                
                itemsVenta = [];
                clienteSeleccionado = null;
                clienteSelect.value = "";
                infoCliente.style.display = "none";
                renderTabla();
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                setTimeout(() => {
                    window.location.reload();
                }, 15000);
            } else {
                notificacion.className = "mensajeError";
                notificacion.innerHTML = data.message;
                notificacion.style.display = "block";
                btnProcesarVenta.disabled = false;
            }
        } catch (e) {
            console.error(e);
            alert("Error al procesar la venta.");
            btnProcesarVenta.disabled = false;
        }
    });
</script>
</body>
</html>

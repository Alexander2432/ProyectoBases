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
require_once(__DIR__ . "/../../model/usuario.php");

$modeloProducto = new Producto();
$todosProductos = $modeloProducto->obtenerTodos();

$productosJson = [];
while ($prod = $todosProductos->fetch_assoc()) {
    $productosJson[] = $prod;
}

$modeloUsuario = new Usuario();
$todosUsuarios = $modeloUsuario->listar();
$usuariosArray = [];
while ($usr = $todosUsuarios->fetch_assoc()) {
    if ($usr['estado'] === 'Activo') {
        $usuariosArray[] = $usr;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/compras/">
<title>Registrar Compra</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
<style>
    .compra-contenedor {
        display: grid;
        grid-template-columns: 1fr 320px;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .compra-contenedor {
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
</style>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Transacciones</p>
        <h2>Registrar Compra</h2>
        <p class="descripcionSeccion">Ingresa facturas de proveedores para reabastecer el inventario de productos. Afecta el stock inmediatamente.</p>
    </div>
</div>

<div id="notificacion" style="display: none; margin-bottom: 20px;"></div>

<div class="compra-contenedor">
    <div class="panel-detalles">
        <h3>Agregar Producto a la Lista</h3>
        
        <div class="fila-busqueda">
            <div>
                <label for="productoSelect">Seleccionar Producto</label>
                <select id="productoSelect" onchange="seleccionarProducto()">
                    <option value="">-- Elige un producto --</option>
                    <?php foreach ($productosJson as $p) { ?>
                        <option value="<?php echo (int)$p['idProducto']; ?>">
                            <?php echo htmlspecialchars($p['codigo'] . ' - ' . $p['nombre']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div>
                <label for="cantidadProducto">Cantidad</label>
                <input type="number" id="cantidadProducto" value="1" min="1" step="1">
            </div>
            <div>
                <label for="precioProducto">Precio Unitario ($)</label>
                <input type="number" id="precioProducto" value="0.00" min="0.0001" step="0.0001">
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
                        <th>Cantidad</th>
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
        <h3>Datos de la Compra</h3>
        
        <div class="grupo-campo">
            <label for="usuarioSelect">Persona Encargada</label>
            <select id="usuarioSelect">
                <?php foreach ($usuariosArray as $usr) { ?>
                    <option value="<?php echo (int)$usr['idUsuario']; ?>" <?php echo $usr['idUsuario'] == $_SESSION['idUsuario'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($usr['nombres'] . ' ' . $usr['apellidos']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>
        
        <div class="grupo-campo">
            <label>Fecha y Hora</label>
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
                <span>TOTAL:</span>
                <span id="resumenTotal">$0.00</span>
            </div>
        </div>
        
        <button type="button" class="botonNuevo" id="btnProcesarCompra" style="width: 100%; margin-top: 20px; background: #10b981; color: black; font-weight: 800;" disabled>
            Procesar Compra
        </button>
    </div>
</div>

</main>

<script>
    const listaCompletaProductos = <?php echo json_encode($productosJson); ?>;
    
    let itemsCompra = [];
    let productoSeleccionado = null;
    
    const productoSelect = document.getElementById("productoSelect");
    const cantidadProducto = document.getElementById("cantidadProducto");
    const precioProducto = document.getElementById("precioProducto");
    const btnAgregarItem = document.getElementById("btnAgregarItem");
    const cuerpoDetalle = document.getElementById("cuerpoDetalle");
    const resumenSubtotal = document.getElementById("resumenSubtotal");
    const resumenIva = document.getElementById("resumenIva");
    const resumenTotal = document.getElementById("resumenTotal");
    const btnProcesarCompra = document.getElementById("btnProcesarCompra");
    const notificacion = document.getElementById("notificacion");
    const usuarioSelect = document.getElementById("usuarioSelect");
    
    function actualizarFecha() {
        const ahora = new Date();
        document.getElementById("fechaHora").value = ahora.toLocaleString("es-EC");
    }
    actualizarFecha();
    
    function seleccionarProducto() {
        const id = parseInt(productoSelect.value);
        if (isNaN(id)) {
            productoSeleccionado = null;
            precioProducto.value = "0.00";
            return;
        }
        
        productoSeleccionado = listaCompletaProductos.find(p => parseInt(p.idProducto) === id);
        if (productoSeleccionado) {
            precioProducto.value = parseFloat(productoSeleccionado.precio).toFixed(4);
        }
    }
    
    btnAgregarItem.addEventListener("click", function() {
        if (!productoSeleccionado) {
            alert("Por favor, selecciona un producto de la lista.");
            return;
        }
        
        const cantidad = parseInt(cantidadProducto.value);
        const precio = parseFloat(precioProducto.value);
        
        if (isNaN(cantidad) || cantidad <= 0 || !Number.isInteger(cantidad)) {
            alert("Ingrese una cantidad entera válida mayor a cero.");
            return;
        }
        if (isNaN(precio) || precio <= 0) {
            alert("Ingrese un precio unitario válido mayor a cero.");
            return;
        }
        
        const index = itemsCompra.findIndex(item => item.idProducto === productoSeleccionado.idProducto);
        
        const subtotal = cantidad * precio;
        const iva = subtotal * (parseFloat(productoSeleccionado.ivaPorcentaje) / 100);
        const total = subtotal + iva;
        
        if (index !== -1) {
            itemsCompra[index].cantidad += cantidad;
            itemsCompra[index].subtotal = itemsCompra[index].cantidad * itemsCompra[index].precio;
            itemsCompra[index].iva = itemsCompra[index].subtotal * (parseFloat(productoSeleccionado.ivaPorcentaje) / 100);
            itemsCompra[index].total = itemsCompra[index].subtotal + itemsCompra[index].iva;
        } else {
            itemsCompra.push({
                idProducto: productoSeleccionado.idProducto,
                codigo: productoSeleccionado.codigo,
                nombre: productoSeleccionado.nombre,
                ivaPorcentaje: parseFloat(productoSeleccionado.ivaPorcentaje),
                cantidad: cantidad,
                precio: precio,
                subtotal: subtotal,
                iva: iva,
                total: total
            });
        }
        
        productoSelect.value = "";
        cantidadProducto.value = "1";
        precioProducto.value = "0.00";
        productoSeleccionado = null;
        
        renderTabla();
    });
    
    function renderTabla() {
        if (itemsCompra.length === 0) {
            cuerpoDetalle.innerHTML = `
                <tr>
                    <td colspan="8" style="text-align: center; color: rgba(255,255,255,0.4); padding: 30px 0;">
                        Ningún producto añadido todavía.
                    </td>
                </tr>`;
            btnProcesarCompra.disabled = true;
            resumenSubtotal.textContent = "$0.00";
            resumenIva.textContent = "$0.00";
            resumenTotal.textContent = "$0.00";
            return;
        }
        
        cuerpoDetalle.innerHTML = "";
        let subtotalAcumulado = 0;
        let ivaAcumulado = 0;
        
        itemsCompra.forEach((item, index) => {
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
        
        btnProcesarCompra.disabled = false;
    }
    
    window.eliminarItem = function(index) {
        itemsCompra.splice(index, 1);
        renderTabla();
    };
    
    btnProcesarCompra.addEventListener("click", async function() {
        if (itemsCompra.length === 0) return;
        
        if (!confirm("¿Está seguro de procesar esta compra? El stock se actualizará de inmediato.")) {
            return;
        }
        
        btnProcesarCompra.disabled = true;
        
        try {
            const res = await fetch("<?php echo urlLimpia('controller/compraController.php'); ?>", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    idUsuario: parseInt(usuarioSelect.value),
                    productos: itemsCompra
                })
            });
            const data = await res.json();
            
            if (data.success) {
                notificacion.className = "mensajeExito";
                notificacion.innerHTML = data.message;
                notificacion.style.display = "block";
                itemsCompra = [];
                renderTabla();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } else {
                notificacion.className = "mensajeError";
                notificacion.innerHTML = data.message;
                notificacion.style.display = "block";
                btnProcesarCompra.disabled = false;
            }
        } catch (e) {
            console.error("Error al procesar la compra", e);
            alert("Ocurrió un error al procesar la compra.");
            btnProcesarCompra.disabled = false;
        }
    });
</script>
</body>
</html>

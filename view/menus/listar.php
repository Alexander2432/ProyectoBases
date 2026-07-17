<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();
if(!isset($_SESSION["idUsuario"])){
    require_once(__DIR__ . "/../../config/constantes.php");
    header("Location: " . urlLimpia("view/index.html"));
    exit();
}
require_once(__DIR__ . "/../../config/constantes.php");
require_once(__DIR__ . "/../../model/modeloMenu.php");

$modelo = new Menu();

$limite = 10;
$paginaActual = isset($_GET["pag"]) ? max(1, (int)$_GET["pag"]) : 1;
$totalMenus = $modelo->contarTotal();
$totalPaginas = ceil($totalMenus / $limite);
$offset = ($paginaActual - 1) * $limite;

$menus = $modelo->obtenerTodosPaginado($offset, $limite);
$menusPadre = $modelo->obtenerMenusPadre();
$paginasDisponibles = require(__DIR__ . "/../../config/paginasDisponibles.php");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<base href="<?php echo BASE_URL; ?>view/menus/">
<title>Men&uacute;s &middot; Sistema de Gesti&oacute;n</title>
<link rel="stylesheet" href="../../estilos/dashboard.css">
<script src="../../controller/logout.js"></script>
</head>
<body>
<?php require_once(__DIR__ . "/../menu.php"); ?>
<main class="contenido">

<div class="encabezadoSeccion">
    <div>
        <p class="eyebrow">Navegaci&oacute;n</p>
        <h2>Estructura de men&uacute;s</h2>
        <p class="descripcionSeccion">Organiza la navegaci&oacute;n y luego asigna visibilidad desde permisos.</p>
    </div>
    <button class="botonNuevo" onclick="abrirModalCrear()">Crear opci&oacute;n</button>
</div>

<?php
if(isset($_GET["mensaje"])){
    if($_GET["mensaje"]=="ok"){
        echo '<div class="mensajeExito">Cambios guardados correctamente.</div>';
    }else if($_GET["mensaje"]=="eliminado"){
        echo '<div class="mensajeExito">Opci&oacute;n de men&uacute; eliminada correctamente.</div>';
    }else if($_GET["mensaje"]=="tieneSubmenus"){
        echo '<div class="mensajeError">No se puede eliminar: este men&uacute; todav&iacute;a tiene submen&uacute;s.</div>';
    }else if($_GET["mensaje"]=="error"){
        echo '<div class="mensajeError">Completa nombre y URL antes de guardar.</div>';
    }
}
?>

<p class="descripcionSeccion enlaceAyuda">
    Para controlar qu&eacute; rol ve cada opci&oacute;n, entra a
    <a href="<?php echo urlLimpia("view/permisos/asignar.php"); ?>">Asignar permisos</a>.
</p>

<div class="tablaPanel">
<table>
<thead>
<tr>
<th>Orden</th>
<th>Nombre</th>
<th>URL</th>
<th>Estado</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php if($menus->num_rows > 0){ ?>
<?php while($fila = $menus->fetch_assoc()){ 
    $activo = (int) $fila["activo"] === 1;
    $conSangria = !is_null($fila["idMenuPadre"]);
    $tieneHijos = $modelo->tieneSubmenus($fila["idMenu"]);
    $nombreMostrado = $conSangria
        ? '<span style="padding-left: 20px; color: var(--color-primary);">&rarr; ' . htmlspecialchars($fila["nombre"]) . '</span>'
        : '<strong>' . htmlspecialchars($fila["nombre"]) . '</strong>';
?>
<tr<?php echo $activo ? '' : ' class="filaInactiva"'; ?>>
<td data-label="Orden"><?php echo (int) $fila["ordenMenu"]; ?></td>
<td data-label="Nombre"><?php echo $nombreMostrado; ?></td>
<td data-label="URL"><?php echo htmlspecialchars($fila["url"]); ?></td>
<td data-label="Estado">
    <span class="insignia <?php echo $activo ? "activo" : "inactivo"; ?>">
        <?php echo $activo ? "Activo" : "Inactivo"; ?>
    </span>
</td>
<td data-label="Acciones">
    <div class="accionesTabla">
        <button class="accion accionPrimaria" 
                onclick="abrirModalModificar({
                    idMenu: <?php echo (int) $fila['idMenu']; ?>,
                    nombre: '<?php echo addslashes($fila['nombre']); ?>',
                    url: '<?php echo addslashes($fila['url']); ?>',
                    ordenMenu: <?php echo (int) $fila['ordenMenu']; ?>,
                    idMenuPadre: '<?php echo $fila['idMenuPadre'] ?? ''; ?>'
                })">Modificar</button>

        <form action="<?php echo urlLimpia("controller/menuController.php"); ?>" method="POST"
            onsubmit="return confirm('¿<?php echo $activo ? 'Desactivar' : 'Activar'; ?> esta opcion de menu?');">
            <input type="hidden" name="accion" value="cambiarEstado">
            <input type="hidden" name="idMenu" value="<?php echo (int) $fila["idMenu"]; ?>">
            <input type="hidden" name="activo" value="<?php echo $activo ? '0' : '1'; ?>">
            <button type="submit" class="accion">
                <?php echo $activo ? 'Desactivar' : 'Activar'; ?>
            </button>
        </form>

        <?php if($tieneHijos){ ?>
            <span class="textoMuted">Eliminar bloqueado</span>
        <?php }else{ ?>
            <form action="<?php echo urlLimpia("controller/menuController.php"); ?>" method="POST"
                onsubmit="return confirm('Esto borra la opcion de menu de forma definitiva. Continuar?');">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="idMenu" value="<?php echo (int) $fila["idMenu"]; ?>">
                <button type="submit" class="accion accionPeligro">Eliminar</button>
            </form>
        <?php } ?>
    </div>
</td>
</tr>
<?php } ?>
<?php }else{ ?>
<tr>
<td colspan="5">
    <div class="estadoVacio">
        <div class="icono">--</div>
        No hay opciones de men&uacute; registradas.
    </div>
</td>
</tr>
<?php } ?>
</tbody>
</table>
</div>


<?php if($totalPaginas > 1){ ?>
<div class="paginacion">
    <a href="<?php echo urlLimpia("view/menus/listar.php?pag=" . ($paginaActual - 1)); ?>" class="paginacion-btn <?php echo ($paginaActual <= 1) ? 'deshabilitada' : ''; ?>">&laquo; Ant</a>
    <?php for($i = 1; $i <= $totalPaginas; $i++){ ?>
        <a href="<?php echo urlLimpia("view/menus/listar.php?pag=" . $i); ?>" class="paginacion-btn <?php echo ($i == $paginaActual) ? 'activa' : ''; ?>"><?php echo $i; ?></a>
    <?php } ?>
    <a href="<?php echo urlLimpia("view/menus/listar.php?pag=" . ($paginaActual + 1)); ?>" class="paginacion-btn <?php echo ($paginaActual >= $totalPaginas) ? 'deshabilitada' : ''; ?>">Sig &raquo;</a>
</div>
<?php } ?>

</main>


<div class="modal-overlay" id="modalCrear">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Crear opción de menú</h3>
            <button class="modal-close" onclick="cerrarModalCrear()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/menuController.php"); ?>" method="POST" class="formulario" id="formMenuCrear">
            <input type="hidden" name="accion" value="guardar">
            
            <label>Nombre visible en el menú</label>
            <input type="text" name="nombre" placeholder="Ej. Facturas" required>
            
            <label>¿A qué menú pertenece?</label>
            <select name="idMenuPadre" id="idMenuPadreCrear">
                <option value="">Opción principal (nivel superior)</option>
                <?php 
                $menusPadre->data_seek(0);
                while($padre = $menusPadre->fetch_assoc()){ 
                ?>
                <option value="<?php echo (int) $padre["idMenu"]; ?>">
                    Submenú de: <?php echo htmlspecialchars($padre["nombre"]); ?>
                </option>
                <?php } ?>
            </select>
            
            <div id="bloqueEsGrupoCrear" class="campoCheckbox">
                <label>
                    <input type="checkbox" name="esGrupo" id="esGrupoCrear" value="1">
                    <span>Este menú solo agrupa submenús y no lleva a una página propia.</span>
                </label>
            </div>
            
            <div id="bloqueUrlCrear">
                <label>Página de destino</label>
                <select name="url" id="urlCrear" required onchange="verificarNuevaPagina(this)">
                    <option value="">Selecciona una página</option>
                    <?php foreach($paginasDisponibles as $etiqueta => $ruta){ ?>
                    <option value="<?php echo htmlspecialchars($ruta); ?>">
                        <?php echo htmlspecialchars($etiqueta); ?>
                    </option>
                    <?php } ?>
                    <option value="_nueva_pagina_">+ Nueva página de destino...</option>
                </select>
            </div>
            
            <label>Orden</label>
            <input type="text" name="ordenMenu" placeholder="Ej. 20" required>
            
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalCrear()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar opción">
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalModificar">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Modificar opción de menú</h3>
            <button class="modal-close" onclick="cerrarModalModificar()">&times;</button>
        </div>
        <form action="<?php echo urlLimpia("controller/menuController.php"); ?>" method="POST" class="formulario" id="formMenuModificar">
            <input type="hidden" name="accion" value="actualizar">
            <input type="hidden" name="idMenu" id="mod_idMenu">
            
            <label>Nombre visible en el menú</label>
            <input type="text" name="nombre" id="mod_nombre" required>
            
            <label>¿A qué menú pertenece?</label>
            <select name="idMenuPadre" id="idMenuPadreModificar">
                <option value="">Opción principal (nivel superior)</option>
                <?php 
                $menusPadre->data_seek(0);
                while($padre = $menusPadre->fetch_assoc()){ 
                ?>
                <option value="<?php echo (int) $padre["idMenu"]; ?>" id="optPadre_<?php echo $padre['idMenu']; ?>">
                    Submenú de: <?php echo htmlspecialchars($padre["nombre"]); ?>
                </option>
                <?php } ?>
            </select>
            
            <div id="bloqueEsGrupoModificar" class="campoCheckbox">
                <label>
                    <input type="checkbox" name="esGrupo" id="esGrupoModificar" value="1">
                    <span>Este menú solo agrupa submenús y no lleva a una página propia.</span>
                </label>
            </div>
            
            <div id="bloqueUrlModificar">
                <label>Página de destino</label>
                <select name="url" id="urlModificar" required onchange="verificarNuevaPagina(this)">
                    <option value="">Selecciona una página</option>
                    <?php foreach($paginasDisponibles as $etiqueta => $ruta){ ?>
                    <option value="<?php echo htmlspecialchars($ruta); ?>">
                        <?php echo htmlspecialchars($etiqueta); ?>
                    </option>
                    <?php } ?>
                    <option value="_nueva_pagina_">+ Nueva página de destino...</option>
                </select>
            </div>
            
            <label>Orden</label>
            <input type="text" name="ordenMenu" id="mod_ordenMenu" required>
            
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalModificar()">Cancelar</button>
                <input type="submit" class="accionPrimaria" value="Guardar cambios">
            </div>
        </form>
    </div>
</div>


<div class="modal-overlay" id="modalNuevaPagina" style="z-index: 10005;">
    <div class="modal-container" style="max-width: 450px;">
        <div class="modal-header">
            <h3>Nueva página de destino</h3>
            <button class="modal-close" onclick="cerrarModalNuevaPagina()">&times;</button>
        </div>
        <div class="formulario">
            <label>Nombre / Etiqueta</label>
            <input type="text" id="np_etiqueta" placeholder="Ej. Reporte Diario" required>
            
            <label>Ruta (URL / Archivo PHP)</label>
            <input type="text" id="np_ruta" placeholder="Ej. view/reportes/diario.php" required>
            
            <div class="accionesFormulario">
                <button type="button" class="botonSecundario" onclick="cerrarModalNuevaPagina()">Cancelar</button>
                <button type="button" class="accionPrimaria" onclick="guardarNuevaPagina()">Guardar página</button>
            </div>
        </div>
    </div>
</div>

<script>
var selectActivo = null;

function abrirModalCrear() {
    document.getElementById("modalCrear").classList.add("active");
    actualizarCamposGrupo("Crear");
}
function cerrarModalCrear() {
    document.getElementById("modalCrear").classList.remove("active");
}
function abrirModalModificar(datos) {
    document.getElementById("mod_idMenu").value = datos.idMenu;
    document.getElementById("mod_nombre").value = datos.nombre;
    document.getElementById("mod_ordenMenu").value = datos.ordenMenu;
    
    
    var selectPadre = document.getElementById("idMenuPadreModificar");
    selectPadre.value = datos.idMenuPadre;
    
    
    for (var i = 0; i < selectPadre.options.length; i++) {
        if (selectPadre.options[i].value == datos.idMenu) {
            selectPadre.options[i].style.display = "none";
        } else {
            selectPadre.options[i].style.display = "block";
        }
    }
    
    
    var selectUrl = document.getElementById("urlModificar");
    var checkEsGrupo = document.getElementById("esGrupoModificar");
    if (datos.url === "#" && datos.idMenuPadre === "") {
        checkEsGrupo.checked = true;
        selectUrl.value = "";
    } else {
        checkEsGrupo.checked = false;
        selectUrl.value = datos.url;
    }
    
    document.getElementById("modalModificar").classList.add("active");
    actualizarCamposGrupo("Modificar");
}
function cerrarModalModificar() {
    document.getElementById("modalModificar").classList.remove("active");
}

function actualizarCamposGrupo(tipo) {
    var selectPadre = document.getElementById("idMenuPadre" + tipo);
    var bloqueEsGrupo = document.getElementById("bloqueEsGrupo" + tipo);
    var checkEsGrupo = document.getElementById("esGrupo" + tipo);
    var bloqueUrl = document.getElementById("bloqueUrl" + tipo);
    var selectUrl = document.getElementById("url" + tipo);

    var esNivelSuperior = selectPadre.value === "";
    bloqueEsGrupo.style.display = esNivelSuperior ? "block" : "none";
    if (!esNivelSuperior) {
        checkEsGrupo.checked = false;
    }

    var ocultarUrl = esNivelSuperior && checkEsGrupo.checked;
    bloqueUrl.style.display = ocultarUrl ? "none" : "block";
    selectUrl.required = !ocultarUrl;
}

document.getElementById("idMenuPadreCrear").addEventListener("change", function() { actualizarCamposGrupo("Crear"); });
document.getElementById("esGrupoCrear").addEventListener("change", function() { actualizarCamposGrupo("Crear"); });
document.getElementById("idMenuPadreModificar").addEventListener("change", function() { actualizarCamposGrupo("Modificar"); });
document.getElementById("esGrupoModificar").addEventListener("change", function() { actualizarCamposGrupo("Modificar"); });

function verificarNuevaPagina(select) {
    if (select.value === "_nueva_pagina_") {
        selectActivo = select;
        abrirModalNuevaPagina();
    }
}

function abrirModalNuevaPagina() {
    document.getElementById("np_etiqueta").value = "";
    document.getElementById("np_ruta").value = "";
    document.getElementById("modalNuevaPagina").classList.add("active");
}
function cerrarModalNuevaPagina() {
    document.getElementById("modalNuevaPagina").classList.remove("active");
    if (selectActivo) {
        selectActivo.value = "";
    }
}

function guardarNuevaPagina() {
    var etiqueta = document.getElementById("np_etiqueta").value.trim();
    var ruta = document.getElementById("np_ruta").value.trim();
    
    if (etiqueta === "" || ruta === "") {
        alert("Completa ambos campos por favor.");
        return;
    }
    
    var formData = new FormData();
    formData.append("accion", "agregarPagina");
    formData.append("etiqueta", etiqueta);
    formData.append("ruta", ruta);
    
    fetch("../../controller/menuController.php", {
        method: "POST",
        body: formData
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            
            var selectC = document.getElementById("urlCrear");
            var selectM = document.getElementById("urlModificar");
            
            [selectC, selectM].forEach(function(select) {
                var opt = document.createElement("option");
                opt.value = data.ruta;
                opt.textContent = data.etiqueta;
                
                select.insertBefore(opt, select.lastElementChild);
            });
            
            if (selectActivo) {
                selectActivo.value = data.ruta;
            }
            
            document.getElementById("modalNuevaPagina").classList.remove("active");
            alert("Página agregada correctamente.");
        } else {
            alert("Error: " + data.mensaje);
        }
    })
    .catch(function(err) {
        alert("No se pudo conectar con el servidor.");
        console.error(err);
    });
}
window.addEventListener("DOMContentLoaded", function() {
    var urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has("crear")) {
        abrirModalCrear();
    }
});
</script>
</body>
</html>

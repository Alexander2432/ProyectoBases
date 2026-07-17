const listaMenu       = document.getElementById("menuPrincipal");
const tituloBienvenida = document.getElementById("tituloBienvenida");
const fechaHoy         = document.getElementById("fechaHoy");
const tarjetas         = document.getElementById("tarjetas");
const accesosRapidos   = document.getElementById("accesosRapidos");
const textoPanel       = document.getElementById("textoPanel");
const eyebrowPanel     = document.getElementById("eyebrowPanel");

function obtenerBase(){
    if(window.BASE_URL){
        const b = window.BASE_URL;
        return b.endsWith("/") ? b : b + "/";
    }
    const base = document.querySelector("base[href]");
    if(base && base.href) return new URL("..", base.href).href;
    return window.location.origin + "/";
}
const BASE = obtenerBase();

function url(ruta){ return BASE + ruta; }

const formateadorFecha = new Intl.DateTimeFormat("es-EC", {
    weekday: "long", year: "numeric", month: "long", day: "numeric"
});

function resolverUrl(ruta){
    if(!ruta || ruta === "#") return "javascript:void(0)";
    if(ruta.startsWith("http")) return ruta;
    return url(ruta);
}

function construirMenu(menus){
    const frag = document.createDocumentFragment();

    menus.forEach(function(item){
        const li     = document.createElement("li");
        const enlace = document.createElement("a");
        enlace.href       = resolverUrl(item.url);
        enlace.textContent = item.nombre;
        li.appendChild(enlace);

        if(item.submenus && item.submenus.length > 0){
            const ul = document.createElement("ul");
            ul.className = "submenu";
            item.submenus.forEach(function(sub){
                const subLi    = document.createElement("li");
                const subEnlace = document.createElement("a");
                subEnlace.href       = resolverUrl(sub.url);
                subEnlace.textContent = sub.nombre;
                subLi.appendChild(subEnlace);
                ul.appendChild(subLi);
            });
            li.appendChild(ul);
        }
        frag.appendChild(li);
    });

    const liLogout    = document.createElement("li");
    liLogout.className = "logout";
    const aLogout     = document.createElement("a");
    aLogout.href      = url("controller/logout.php");
    aLogout.className = "btnCerrarSesion";
    aLogout.textContent = "Cerrar sesion";
    liLogout.appendChild(aLogout);
    frag.appendChild(liLogout);

    listaMenu.appendChild(frag);
}

function construirTarjetas(stats, nombreRol){
    const datos = [{ clase:"rol", icono:"Rol", valor: nombreRol || "-", etiqueta:"Tu rol actual" }];
    if(stats.puedeVerEstadisticas){
        datos.unshift(
            { clase:"activos",   icono:"OK", valor: stats.usuariosActivos,   etiqueta:"Usuarios activos"   },
            { clase:"inactivos", icono:"!",  valor: stats.usuariosInactivos, etiqueta:"Usuarios inactivos" }
        );
    }
    tarjetas.innerHTML = "";
    datos.forEach(function(d){
        const div = document.createElement("div");
        div.className = "tarjeta " + d.clase;
        div.innerHTML = `
            <div class="icono">${d.icono}</div>
            <div><div class="valor">${d.valor}</div>
            <div class="etiqueta">${d.etiqueta}</div></div>`;
        tarjetas.appendChild(div);
    });
}

function construirAccesos(menus){
    const accesos = [];
    menus.forEach(function(item){
        if(item.url && item.url !== "#" &&
           item.url !== "view/dashboard.php" && item.url !== "view/dashboard.html" &&
           item.url !== "dashboard"){
            accesos.push(item);
        }
        if(item.submenus) item.submenus.forEach(function(sub){
            if(sub.url && sub.url !== "#") accesos.push(sub);
        });
    });

    accesosRapidos.innerHTML = "";

    if(accesos.length === 0){
        const vacio = document.createElement("div");
        vacio.className   = "panelSimple accesoVacio";
        vacio.textContent = "No tienes accesos adicionales asignados por ahora.";
        accesosRapidos.appendChild(vacio);
        return;
    }

    accesos.forEach(function(item, i){
        const enlace      = document.createElement("a");
        enlace.className  = "accesoRapido";
        enlace.href       = resolverUrl(item.url);

        const numero = document.createElement("span");
        numero.className  = "accesoNumero";
        numero.textContent = String(i + 1).padStart(2, "0");

        const texto = document.createElement("span");
        texto.className   = "accesoTexto";
        texto.textContent = item.nombre;

        const flecha = document.createElement("span");
        flecha.className  = "accesoFlecha";
        flecha.textContent = ">";

        enlace.appendChild(numero);
        enlace.appendChild(texto);
        enlace.appendChild(flecha);
        accesosRapidos.appendChild(enlace);
    });
}

async function iniciar(){
    fechaHoy.textContent = formateadorFecha.format(new Date());

    try{
        const r = await fetch(url("controller/sesionController.php"), { credentials: "same-origin" });

        if(r.status === 401){
            window.location.href = url("view/index.html");
            return;
        }

        const datos = await r.json();
        if(!datos.success){
            window.location.href = url("view/index.html");
            return;
        }
        sessionStorage.setItem("sesion_activa", "true");
        if (window.parent && window.parent !== window) {
            window.parent.sessionStorage.setItem("sesion_activa", "true");
        }

        const nombreCompleto = (datos.usuario.nombres + " " + datos.usuario.apellidos).trim()
                             || datos.usuario.usuario;

        tituloBienvenida.textContent = "Bienvenido, " + nombreCompleto;
        eyebrowPanel.textContent = datos.estadisticas.puedeVerEstadisticas
            ? "Resumen administrativo" : "Resumen personal";
        textoPanel.textContent = datos.estadisticas.puedeVerEstadisticas
            ? "Supervisa el estado general del sistema y entra rapidamente a las areas de administracion."
            : "Este espacio muestra solo las opciones habilitadas para tu rol, para que encuentres rapido lo que puedes usar.";

        construirMenu(datos.menu);
        construirTarjetas(datos.estadisticas, datos.usuario.nombreRol);
        construirAccesos(datos.menu);

    }catch(err){
        tituloBienvenida.textContent = "No se pudo cargar la sesion";
        console.error(err);
    }
}

iniciar();

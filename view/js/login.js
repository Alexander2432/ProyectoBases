document.getElementById("anio").textContent = new Date().getFullYear();

const formulario    = document.getElementById("formLogin");
const campoUsuario  = document.getElementById("usuario");
const campoPassword = document.getElementById("password");
const campoCaptcha  = document.getElementById("captcha");
const captchaTexto  = document.getElementById("captchaPregunta");
const btnRefrescar  = document.getElementById("btnRefrescarCaptcha");
const btnIngresar   = document.getElementById("btnIngresar");
const togglePassword = document.getElementById("togglePassword");
const mensaje       = document.getElementById("mensaje");

function obtenerBase(){
    if(window.BASE_URL){
        const b = window.BASE_URL;
        return b.endsWith("/") ? b : b + "/";
    }
    const base = document.querySelector("base[href]");
    if(base && base.href){
        return new URL("..", base.href).href;
    }
    return window.location.origin + "/";
}

const BASE = obtenerBase();

function url(ruta){
    return BASE + ruta;
}

async function cargarCaptcha(){
    captchaTexto.textContent = "cargando...";
    captchaTexto.classList.add("cargando");
    campoCaptcha.value = "";
    try{
        const r = await fetch(url("controller/captchaController.php"), { credentials: "same-origin" });
        if(!r.ok) throw new Error();
        const datos = await r.json();
        captchaTexto.innerHTML = "";
        const colores = ["#E0F7FA","#F8E7A1","#A7F3D0","#FBCFE8","#DDD6FE"];
        datos.codigo.forEach(function(letra, i){
            const span = document.createElement("span");
            span.textContent = letra;
            span.className = "captchaLetra";
            span.style.color = colores[i % colores.length];
            const rot  = (Math.random()*34 - 17).toFixed(1);
            const des  = (Math.random()*8  -  4).toFixed(1);
            const esc  = (0.92 + Math.random()*0.2).toFixed(2);
            span.style.transform = "translateY("+des+"px) rotate("+rot+"deg) scale("+esc+")";
            span.style.zIndex = String(i + 2);
            captchaTexto.appendChild(span);
        });
        captchaTexto.classList.remove("cargando");
    }catch(e){
        captchaTexto.textContent = "Error al cargar";
        captchaTexto.classList.remove("cargando");
    }
}

function marcarError(campo){
    document.querySelectorAll("input.inputError").forEach(el => el.classList.remove("inputError"));
    if(campo){
        if(typeof campo === "string" && campo.includes(",")){
            campo.split(",").forEach(c => {
                const el = document.getElementById(c.trim());
                if(el) el.classList.add("inputError");
            });
        }else{
            const el = document.getElementById(campo);
            if(el) el.classList.add("inputError");
        }
    }
}

function mostrarMensaje(texto, tipo){
    mensaje.textContent = texto;
    mensaje.className   = tipo;
}

togglePassword.addEventListener("click", function(){
    const esPass = campoPassword.type === "password";
    campoPassword.type = esPass ? "text" : "password";
    togglePassword.textContent = esPass ? "Ocultar" : "Ver";
});

btnRefrescar.addEventListener("click", cargarCaptcha);

formulario.addEventListener("submit", async function(e){
    e.preventDefault();
    marcarError(null);
    mostrarMensaje("", "");

    const usuario  = campoUsuario.value.trim();
    const password = campoPassword.value.trim();
    const captcha  = campoCaptcha.value.trim();

    if(usuario === ""){
        mostrarMensaje("Ingrese el usuario.", "error");
        marcarError("usuario");
        return;
    }
    if(password === ""){
        mostrarMensaje("Ingrese la contraseña.", "error");
        marcarError("password");
        return;
    }
    if(captcha === ""){
        mostrarMensaje("Escriba el código de verificación.", "error");
        marcarError("captcha");
        return;
    }

    btnIngresar.classList.add("cargando");
    btnIngresar.disabled = true;

    try{
        const r = await fetch(url("controller/loginController.php"), {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            credentials: "same-origin",
            body: JSON.stringify({ usuario, password, captcha }),
        });

        if(!r.ok) throw new Error("Error de red");

        const datos = await r.json();

        if(datos.success){
            mostrarMensaje(datos.mensaje, "exito");
            sessionStorage.setItem("sesion_activa", "true");
            if (window.parent && window.parent !== window) {
                window.parent.sessionStorage.setItem("sesion_activa", "true");
            }
            window.location.href = url("view/dashboard.html");
            return;
        }

        mostrarMensaje(datos.mensaje, "error");
        marcarError(datos.campo);
        cargarCaptcha();

    }catch(err){
        mostrarMensaje("No se pudo conectar con el servidor. Intente nuevamente.", "error");
    }finally{
        btnIngresar.classList.remove("cargando");
        btnIngresar.disabled = false;
    }
});

async function verificarSesionActiva(){
    try{
        const r = await fetch(url("controller/sesionController.php"), { credentials: "same-origin" });
        if(r.ok){
            const datos = await r.json();
            if(datos.success){
                sessionStorage.setItem("sesion_activa", "true");
                if (window.parent && window.parent !== window) {
                    window.parent.sessionStorage.setItem("sesion_activa", "true");
                }
                window.location.href = url("view/dashboard.html");
                return true;
            }
        }
    }catch(e){}
    return false;
}

async function iniciarLogin(){
    const activa = await verificarSesionActiva();
    if(!activa){
        cargarCaptcha();
    }
}

iniciarLogin();

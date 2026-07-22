const formulario = document.getElementById("formUsuario");
const campoCedula = document.getElementById("cedula");

if (campoCedula) {
    campoCedula.addEventListener("input", function() {
        this.value = this.value.replace(/\D/g, "").slice(0, 10);
    });
}

if (formulario) {
    formulario.addEventListener("submit", function(e) {
        const usuario = document.getElementById("usuario").value.trim();
        const password = document.getElementById("password").value.trim();
        const confirmar = document.getElementById("confirmar").value.trim();
        const mensaje = document.getElementById("mensaje");

        mensaje.textContent = "";
        mensaje.className = "mensajeFormulario";

        if (usuario === "") {
            mensaje.textContent = "Ingrese el usuario";
            mensaje.classList.add("mensajeErrorInline");
            e.preventDefault();
            return;
        }

        if (password.length < 8) {
            mensaje.textContent = "La contrasena debe tener minimo 8 caracteres";
            mensaje.classList.add("mensajeErrorInline");
            e.preventDefault();
            return;
        }

        if (!/[A-Z]/.test(password)) {
            mensaje.textContent = "La contrasena debe tener al menos una letra mayuscula";
            mensaje.classList.add("mensajeErrorInline");
            e.preventDefault();
            return;
        }

        if (!/[^A-Za-z0-9]/.test(password)) {
            mensaje.textContent = "La contrasena debe tener al menos un caracter especial (ej. @, #, $, etc.)";
            mensaje.classList.add("mensajeErrorInline");
            e.preventDefault();
            return;
        }

        if (password !== confirmar) {
            mensaje.textContent = "Las contrasenas no coinciden";
            mensaje.classList.add("mensajeErrorInline");
            e.preventDefault();
        }
    });
}

function cedulaEcuatorianaValida(cedula) {
    if (!/^[0-9]{10}$/.test(cedula)) {
        return false;
    }

    const provincia = Number(cedula.substring(0, 2));
    const tercerDigito = Number(cedula[2]);
    const provinciaValida = (provincia >= 1 && provincia <= 24) || provincia === 30;

    if (!provinciaValida || tercerDigito > 5) {
        return false;
    }

    let suma = 0;
    for (let i = 0; i < 9; i++) {
        let digito = Number(cedula[i]);
        if (i % 2 === 0) {
            digito *= 2;
            if (digito > 9) {
                digito -= 9;
            }
        }
        suma += digito;
    }

    const verificador = (10 - (suma % 10)) % 10;
    return verificador === Number(cedula[9]);
}

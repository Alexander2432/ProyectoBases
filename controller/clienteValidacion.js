document.addEventListener("DOMContentLoaded", function() {
    function validarCedulaEcuatoriana(cedula) {
        const limpia = cedula.trim();
        if (!/^\d+$/.test(limpia)) return false;
        if (limpia.length !== 10 && limpia.length !== 13) return false;
        
        const base = limpia.substring(0, 10);
        const provincia = parseInt(base.substring(0, 2), 10);
        if (provincia < 1 || provincia > 24) return false;
        
        const tercerDigito = parseInt(base.charAt(2), 10);
        if (tercerDigito > 6) return false;
        
        let suma = 0;
        const coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        for (let i = 0; i < 9; i++) {
            let valor = parseInt(base.charAt(i), 10) * coeficientes[i];
            if (valor >= 10) valor -= 9;
            suma += valor;
        }
        
        const verificador = parseInt(base.charAt(9), 10);
        const decenaSuperior = Math.ceil(suma / 10) * 10;
        let resultado = decenaSuperior - suma;
        if (resultado === 10) resultado = 0;
        
        return resultado === verificador;
    }

    const formularios = document.querySelectorAll("form");
    formularios.forEach(form => {
        form.addEventListener("submit", function(e) {
            const accionInput = form.querySelector('input[name="accion"]');
            if (accionInput && (accionInput.value === 'guardar' || accionInput.value === 'modificar')) {
                const cedulaInput = form.querySelector('input[name="cedula"]');
                const nombresInput = form.querySelector('input[name="nombres"]');
                const apellidosInput = form.querySelector('input[name="apellidos"]');
                
                if (nombresInput && nombresInput.value.trim() === "") {
                    alert("Los nombres son obligatorios.");
                    e.preventDefault();
                    return;
                }
                if (apellidosInput && apellidosInput.value.trim() === "") {
                    alert("Los apellidos son obligatorios.");
                    e.preventDefault();
                    return;
                }
                if (cedulaInput && !validarCedulaEcuatoriana(cedulaInput.value)) {
                    alert("La cédula o RUC ingresado no es válido para Ecuador.");
                    e.preventDefault();
                    return;
                }
            }
        });
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const formularios = document.querySelectorAll("form");
    formularios.forEach(form => {
        form.addEventListener("submit", function(e) {
            const accionInput = form.querySelector('input[name="accion"]');
            if (accionInput && (accionInput.value === 'guardar' || accionInput.value === 'modificar')) {
                const codigoInput = form.querySelector('input[name="codigo"]');
                const nombreInput = form.querySelector('input[name="nombre"]');
                const precioInput = form.querySelector('input[name="precio"]');
                const stockInput = form.querySelector('input[name="stock"]');
                
                if (codigoInput && codigoInput.value.trim() === "") {
                    alert("El código de producto es obligatorio.");
                    e.preventDefault();
                    return;
                }
                if (nombreInput && nombreInput.value.trim() === "") {
                    alert("El nombre de producto es obligatorio.");
                    e.preventDefault();
                    return;
                }
                if (precioInput) {
                    const precio = parseFloat(precioInput.value);
                    if (isNaN(precio) || precio <= 0) {
                        alert("El precio de venta debe ser un valor mayor a cero.");
                        e.preventDefault();
                        return;
                    }
                }
                if (stockInput) {
                    const stock = parseFloat(stockInput.value);
                    if (isNaN(stock) || stock < 0 || !Number.isInteger(stock)) {
                        alert("El stock inicial debe ser un número entero mayor o igual a cero.");
                        e.preventDefault();
                        return;
                    }
                }
            }
        });
    });
});

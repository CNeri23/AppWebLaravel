document.addEventListener('DOMContentLoaded', function () {
    const tablas = document.querySelectorAll('.table-responsive table.table');

    tablas.forEach(function (tabla) {
        if (tabla.closest('.modal')) {
            return;
        }

        const encabezados = Array.from(tabla.querySelectorAll('thead th')).map(function (th) {
            return th.textContent.trim().replace(/\s+/g, ' ');
        });

        if (!encabezados.length) {
            return;
        }

        tabla.classList.add('responsive-cards-table');

        function aplicarEtiquetas() {
            tabla.querySelectorAll('tbody tr').forEach(function (fila) {
                if (fila.classList.contains('child')) {
                    return;
                }

                Array.from(fila.children).forEach(function (celda, indice) {
                    if (celda.tagName !== 'TD') {
                        return;
                    }

                    const etiqueta = encabezados[indice] || '';
                    if (etiqueta && !celda.classList.contains('dataTables_empty')) {
                        celda.setAttribute('data-label', etiqueta);
                    }
                });
            });
        }

        aplicarEtiquetas();

        const cuerpo = tabla.querySelector('tbody');
        if (cuerpo) {
            const observador = new MutationObserver(aplicarEtiquetas);
            observador.observe(cuerpo, { childList: true, subtree: true });
        }
    });
});

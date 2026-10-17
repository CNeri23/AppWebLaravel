document.addEventListener('DOMContentLoaded', function () {
    const tablas = document.querySelectorAll('table.table');

    tablas.forEach(function (tabla) {
        if (tabla.closest('.modal')) {
            return;
        }
        const encabezados = Array.from(tabla.querySelectorAll('thead th')).map(function (th) {
            return th.textContent.trim().replace(/\s+/g, ' ');
        });

        const indiceAcciones = encabezados.findIndex(function (texto) {
            return /^acciones$/i.test(texto);
        });

        function aplicarEstiloAcciones() {
            if (indiceAcciones < 0) return;

            tabla.querySelectorAll('tbody tr').forEach(function (fila) {
                if (fila.classList.contains('child')) return;
                const celda = fila.children[indiceAcciones];
                if (!celda || celda.tagName !== 'TD') return;

                celda.querySelectorAll('a, button').forEach(function (control) {
                    if (control.closest('.dropdown-menu')) return;

                    const tooltip = control.getAttribute('data-tooltip')
                        || control.getAttribute('aria-label')
                        || control.getAttribute('title')
                        || control.getAttribute('data-bs-original-title');

                    if (tooltip && !control.hasAttribute('data-tooltip')) {
                        control.setAttribute('data-tooltip', tooltip.trim());
                    }
                    if (control.hasAttribute('title')) control.removeAttribute('title');
                    if (control.hasAttribute('data-bs-toggle') && control.getAttribute('data-bs-toggle') === 'tooltip') {
                        control.removeAttribute('data-bs-toggle');
                    }

                    if (tabla.id === 'tablaUsuarios') return;

                    const icono = control.querySelector('i, svg');
                    const clases = `${control.className || ''} ${icono?.className?.baseVal || icono?.className || ''}`.toLowerCase();
                    const texto = `${control.getAttribute('aria-label') || ''} ${control.getAttribute('title') || ''} ${control.textContent || ''}`.toLowerCase();

                    control.classList.add('table-action-btn');
                    control.classList.remove('table-action-primary', 'table-action-success', 'table-action-danger', 'table-action-warning', 'table-action-info');

                    if (/trash|delete|eliminar|borrar|remove|fa-ban/.test(`${clases} ${texto}`) || /danger/.test(clases)) {
                        control.classList.add('table-action-danger');
                    } else if (/edit|pen|pencil|editar|modificar/.test(`${clases} ${texto}`) || /primary/.test(clases)) {
                        control.classList.add('table-action-primary');
                    } else if (/role|shield|user-check|users|rol|permiso|asignar/.test(`${clases} ${texto}`) || /success/.test(clases)) {
                        control.classList.add('table-action-success');
                    } else if (/key|password|lock|clave|contrase/.test(`${clases} ${texto}`) || /warning/.test(clases)) {
                        control.classList.add('table-action-warning');
                    } else if (/eye|view|ver|info/.test(`${clases} ${texto}`) || /info/.test(clases)) {
                        control.classList.add('table-action-info');
                    }

                });
            });
        }

        if (!encabezados.length) {
            return;
        }
        if (tabla.closest('.table-responsive')) tabla.classList.add('responsive-cards-table');
        aplicarEstiloAcciones();

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
            const observador = new MutationObserver(function () {
                aplicarEtiquetas();
                aplicarEstiloAcciones();
            });
            observador.observe(cuerpo, { childList: true, subtree: true });
        }
    });
});
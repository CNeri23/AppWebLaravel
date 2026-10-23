document.addEventListener('DOMContentLoaded', function () {
    if (typeof DataTable === 'undefined') {
        console.error('Alimentación: DataTables no está disponible.');
        return;
    }

    const configuracionComun = {
        autoWidth: false,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        language: {
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_ registros',
            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
            infoFiltered: '(filtrado de _MAX_ registros)',
            emptyTable: 'No hay registros para mostrar',
            zeroRecords: 'No se encontraron coincidencias',
            paginate: {
                first: '<i class="fa-solid fa-angles-left"></i>',
                previous: '<i class="fa-solid fa-angle-left"></i>',
                next: '<i class="fa-solid fa-angle-right"></i>',
                last: '<i class="fa-solid fa-angles-right"></i>'
            }
        },
        layout: {
            topStart: 'pageLength',
            topEnd: 'search',
            bottomStart: 'info',
            bottomEnd: 'paging'
        }
    };

    [
        { id: 'tablaAlimentos', orden: [[0, 'asc']], acciones: 8 },
        { id: 'tablaRegistrosComida', orden: [[0, 'asc']], acciones: 7 },
        { id: 'tablaPanelNutricional', orden: [[0, 'asc']], acciones: null }
    ].forEach(function (opciones) {
        const tabla = document.getElementById(opciones.id);
        if (!tabla || DataTable.isDataTable(tabla)) return;

        const config = {
            ...configuracionComun,
            order: opciones.orden
        };

        if (opciones.acciones !== null) {
            config.columnDefs = [{
                targets: opciones.acciones,
                orderable: false,
                searchable: false
            }];
        }

        new DataTable(tabla, config);

    // Validación de formularios con el mismo patrón visual de Usuarios:
    // validar al escribir/cambiar, mostrar is-invalid/is-valid y enfocar el primer error.
    function feedback(input) {
        return input.parentElement?.querySelector('.invalid-feedback')
            || input.closest('.mb-3, .col-md-3, .col-md-4, .col-md-6, .col-md-8, .col-md-12')?.querySelector('.invalid-feedback')
            || input.parentElement?.parentElement?.querySelector('.invalid-feedback');
    }

    function limpiarValidacion(input) {
        input.classList.remove('is-invalid', 'is-valid');
        input.removeAttribute('aria-invalid');
        const aviso = feedback(input);
        if (aviso) aviso.textContent = '';
    }

    function validarCampo(input) {
        limpiarValidacion(input);
        let mensaje = '';
        const valor = String(input.value ?? '').trim();

        if (input.required && !valor) {
            mensaje = 'Este campo es obligatorio.';
        } else if (input.type === 'number' && valor !== '' && !input.checkValidity()) {
            mensaje = Number(input.min) > 0
                ? 'Ingresa una cantidad mayor que cero.'
                : 'Ingresa un valor numérico válido, igual o mayor que cero.';
        } else if (input.name === 'nombre' && valor.length > 150) {
            mensaje = 'No puede superar 150 caracteres.';
        } else if (input.name === 'categoria' && valor.length > 80) {
            mensaje = 'No puede superar 80 caracteres.';
        } else if (input.name === 'nombre_porcion' && valor.length > 80) {
            mensaje = 'No puede superar 80 caracteres.';
        } else if (input.name === 'notas' && valor.length > 2000) {
            mensaje = 'No puede superar 2000 caracteres.';
        }

        const aviso = feedback(input);
        if (mensaje) {
            input.classList.add('is-invalid');
            input.setAttribute('aria-invalid', 'true');
            if (aviso) aviso.textContent = mensaje;
            return false;
        }

        if (valor || input.required) {
            input.classList.add('is-valid');
            input.setAttribute('aria-invalid', 'false');
        }
        return true;
    }

    function inicializarValidacion(form, modal) {
        if (!form) return;
        const campos = () => Array.from(form.querySelectorAll('input[name], select[name], textarea[name]'))
            .filter(input => ![' _token', '_method'].includes(input.name) && input.type !== 'hidden');

        campos().forEach(input => {
            input.addEventListener('input', () => validarCampo(input));
            input.addEventListener('change', () => validarCampo(input));
        });

        form.addEventListener('submit', function (event) {
            let valido = true;
            let primero = null;
            campos().forEach(input => {
                if (!validarCampo(input)) {
                    valido = false;
                    primero ||= input;
                }
            });
            // Cada fila de alimento exige alimento, cantidad positiva y unidad.
            if (form.id === 'formRegistroComida') {
                form.querySelectorAll('.fila-alimento').forEach(row => {
                    row.querySelectorAll('[required]').forEach(input => {
                        if (!validarCampo(input)) {
                            valido = false;
                            primero ||= input;
                        }
                    });
                });
            }
            if (!valido) {
                event.preventDefault();
                primero?.focus();
            }
        });

        if (modal) {
            modal.addEventListener('shown.bs.modal', () => campos().forEach(limpiarValidacion));
            modal.addEventListener('hidden.bs.modal', () => {
                form.querySelectorAll('.is-invalid, .is-valid').forEach(input => limpiarValidacion(input));
                form.querySelectorAll('.invalid-feedback').forEach(el => { el.textContent = ''; });
            });
        }
    }

    inicializarValidacion(document.getElementById('formCrearAlimento'), document.getElementById('modalCrearAlimento'));
    inicializarValidacion(document.getElementById('formEditarAlimento'), document.getElementById('modalEditarAlimento'));
    inicializarValidacion(document.getElementById('formRegistroComida'), document.getElementById('modalRegistrarComida'));
    });
});

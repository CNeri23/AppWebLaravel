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

    // Validación en vivo siguiendo el patrón usado por los módulos Planes y Usuarios.
    function camposFormulario(formulario) {
        return Array.from(formulario.querySelectorAll(
            'input:not([type="hidden"]):not([type="submit"]):not([type="button"]), select, textarea'
        ));
    }

    function limpiarValidacionCampo(campo) {
        campo.classList.remove('is-invalid', 'is-valid');
        campo.setCustomValidity('');
        campo.removeAttribute('aria-invalid');
        const feedbackExistente = campo.parentElement?.querySelector('.js-campo-feedback');
        if (feedbackExistente) feedbackExistente.remove();
        const feedbackFijo = campo.parentElement?.querySelector('.invalid-feedback[data-error-for]');
        if (feedbackFijo) feedbackFijo.textContent = '';
    }

    function mensajeValidacionCampo(campo, formulario) {
        const valor = String(campo.value ?? '').trim();
        if (campo.required && valor === '') return 'Este campo es obligatorio.';
        if (valor === '') return '';

        if (campo.type === 'number') {
            const numero = Number(valor);
            const minimo = campo.min !== '' ? Number(campo.min) : -Infinity;
            const maximo = campo.max !== '' ? Number(campo.max) : Infinity;
            if (!Number.isFinite(numero) || numero < minimo || numero > maximo || !campo.checkValidity()) {
                return minimo > 0
                    ? 'Ingresa una cantidad mayor que cero y válida.'
                    : 'Ingresa un valor numérico válido igual o mayor que cero.';
            }
        }

        if (campo.name === 'nombre' && valor.length > 150) return 'El nombre no puede superar los 150 caracteres.';
        if (campo.name === 'categoria' && valor.length > 80) return 'La categoría no puede superar los 80 caracteres.';
        if (campo.name === 'nombre_porcion' && valor.length > 80) return 'El nombre de porción no puede superar los 80 caracteres.';
        if (campo.name === 'notas' && valor.length > 2000) return 'Las notas no pueden superar los 2000 caracteres.';

        if (formulario.id === 'formRegistroComida' && campo.name.endsWith('[cantidad]')) {
            if (!Number.isFinite(Number(valor)) || Number(valor) <= 0) return 'La cantidad debe ser mayor que cero.';
        }
        return '';
    }

    function validarCampo(campo, formulario) {
        limpiarValidacionCampo(campo);
        const mensaje = mensajeValidacionCampo(campo, formulario);
        if (!mensaje) {
            if (String(campo.value ?? '').trim() !== '' || campo.required) campo.classList.add('is-valid');
            campo.setAttribute('aria-invalid', 'false');
            return true;
        }

        campo.classList.add('is-invalid');
        campo.setAttribute('aria-invalid', 'true');
        campo.setCustomValidity(mensaje);

        const feedbackFijo = campo.parentElement?.querySelector('.invalid-feedback[data-error-for]');
        if (feedbackFijo) {
            feedbackFijo.textContent = mensaje;
        } else {
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback js-campo-feedback';
            feedback.textContent = mensaje;
            campo.insertAdjacentElement('afterend', feedback);
        }
        return false;
    }

    function validarFormulario(formulario) {
        let primerInvalido = null;
        camposFormulario(formulario).forEach(function (campo) {
            if (!validarCampo(campo, formulario) && !primerInvalido) primerInvalido = campo;
        });
        if (primerInvalido) {
            primerInvalido.focus();
            return false;
        }
        camposFormulario(formulario).forEach(campo => {
            if (typeof campo.value === 'string') campo.value = campo.value.trim();
        });
        return true;
    }

    function inicializarValidacionFormularios() {
        const formularios = [
            ['formCrearAlimento', 'modalCrearAlimento'],
            ['formEditarAlimento', 'modalEditarAlimento'],
            ['formRegistroComida', 'modalRegistrarComida']
        ];

        formularios.forEach(([formId, modalId]) => {
            const formulario = document.getElementById(formId);
            const modal = document.getElementById(modalId);
            if (!formulario) return;

            camposFormulario(formulario).forEach(campo => {
                campo.addEventListener('input', () => validarCampo(campo, formulario));
                campo.addEventListener('change', () => validarCampo(campo, formulario));
            });

            formulario.addEventListener('submit', function (evento) {
                if (!validarFormulario(formulario)) {
                    evento.preventDefault();
                    window.showToast?.('warning', 'Completa correctamente los campos marcados.');
                }
            });

            if (modal) {
                modal.addEventListener('show.bs.modal', () => {
                    camposFormulario(formulario).forEach(limpiarValidacionCampo);
                });
                modal.addEventListener('hidden.bs.modal', () => {
                    camposFormulario(formulario).forEach(limpiarValidacionCampo);
                });
            }
        });

        // Las filas de alimentos se crean dinámicamente: también deben validar al escribir.
        const formularioComida = document.getElementById('formRegistroComida');
        formularioComida?.addEventListener('input', function (evento) {
            const campo = evento.target;
            if (campo.matches('.fila-alimento input, .fila-alimento select')) {
                validarCampo(campo, formularioComida);
            }
        });
        formularioComida?.addEventListener('change', function (evento) {
            const campo = evento.target;
            if (campo.matches('.fila-alimento input, .fila-alimento select')) {
                validarCampo(campo, formularioComida);
            }
        });
    }

    inicializarValidacionFormularios();

});

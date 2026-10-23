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
    });

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

    // Evita envíos duplicados y recargas completas; los cambios se envían por AJAX.
    function serializarFormulario(formulario) {
        return Array.from(new FormData(formulario).entries())
            .filter(([key]) => !['_token', '_method'].includes(key))
            .map(([key, value]) => [key, String(value).trim()])
            .sort(([a], [b]) => a.localeCompare(b));
    }

    function sinCambios(formulario) {
        const inicial = formulario.dataset.valoresIniciales;
        if (!inicial) return false;
        return JSON.stringify(serializarFormulario(formulario)) === inicial;
    }

    function guardarSnapshot(formulario) {
        if (formulario) formulario.dataset.valoresIniciales = JSON.stringify(serializarFormulario(formulario));
    }

    function actualizarFilaAlimento(data) {
        const tabla = document.getElementById('tablaAlimentos');
        if (!tabla) return;
        const alimento = data.alimento;
        const dt = DataTable.isDataTable(tabla) ? new DataTable.Api(tabla) : null;
        const fila = tabla.querySelector('tbody tr[data-id="' + alimento.id + '"]');
        if (!fila) {
            // El alta se refleja en el DOM sin recargar la página.
            const tr = document.createElement('tr');
            tr.dataset.id = alimento.id;
            const porcion = alimento.nombre_porcion
                ? '<small class="d-block text-secondary">' + escapeHtml(alimento.nombre_porcion) + ' = ' + escapeHtml(alimento.cantidad_porcion) + ' ' + escapeHtml(alimento.unidad_base) + '</small>'
                : '';
            const estado = Number(alimento.activo) ? 'Activo' : 'Inactivo';
            const clase = Number(alimento.activo) ? 'text-bg-success' : 'text-bg-secondary';
            tr.innerHTML = '<td class="ps-4"><span class="fw-semibold">' + escapeHtml(alimento.nombre) + '</span></td>' +
                '<td>' + escapeHtml(alimento.categoria || '—') + '</td>' +
                '<td><span>' + escapeHtml(String(alimento.unidad_base).toUpperCase()) + '</span>' + porcion + '</td>' +
                '<td>' + Number(alimento.calorias).toFixed(1) + ' kcal</td><td>' + Number(alimento.proteinas).toFixed(1) + ' g</td>' +
                '<td>' + Number(alimento.carbohidratos).toFixed(1) + ' g</td><td>' + Number(alimento.grasas).toFixed(1) + ' g</td>' +
                '<td class="text-center"><span class="badge rounded-pill ' + clase + '">' + estado + '</span></td><td class="text-center px-4">—</td>';
            if (dt) dt.row.add(tr).draw(false); else tabla.querySelector('tbody').appendChild(tr);
            return;
        }
        fila.querySelectorAll('td')[0].innerHTML = '<span class="fw-semibold">' + escapeHtml(alimento.nombre) + '</span>';
        fila.querySelectorAll('td')[1].textContent = alimento.categoria || '—';
        fila.querySelectorAll('td')[2].innerHTML = '<span>' + escapeHtml(String(alimento.unidad_base).toUpperCase()) + '</span>' +
            (alimento.nombre_porcion ? '<small class="d-block text-secondary">' + escapeHtml(alimento.nombre_porcion) + ' = ' + escapeHtml(alimento.cantidad_porcion) + ' ' + escapeHtml(alimento.unidad_base) + '</small>' : '');
        [3,4,5,6].forEach((index, offset) => {
            const val = [alimento.calorias, alimento.proteinas, alimento.carbohidratos, alimento.grasas][offset];
            fila.querySelectorAll('td')[index].textContent = Number(val).toFixed(1) + (index === 3 ? ' kcal' : ' g');
        });
        fila.querySelectorAll('td')[7].innerHTML = '<span class="badge rounded-pill ' + (Number(alimento.activo) ? 'text-bg-success' : 'text-bg-secondary') + '">' + (Number(alimento.activo) ? 'Activo' : 'Inactivo') + '</span>';
        fila.querySelectorAll('td')[8].querySelectorAll('[data-bs-target="#modalEditarAlimento"]').forEach(btn => {
            Object.entries({id: alimento.id, nombre: alimento.nombre, categoria: alimento.categoria, unidad: alimento.unidad_base,
                nombrePorcion: alimento.nombre_porcion, cantidadPorcion: alimento.cantidad_porcion, calorias: alimento.calorias,
                proteinas: alimento.proteinas, carbohidratos: alimento.carbohidratos, grasas: alimento.grasas, activo: alimento.activo ? 1 : 0})
                .forEach(([key, value]) => btn.dataset[key] = value ?? '');
        });
    }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[char]));
    }

    function inicializarEnviosAjax() {
        const forms = [
            document.getElementById('formCrearAlimento'),
            document.getElementById('formEditarAlimento'),
            document.getElementById('formRegistroComida'),
            document.getElementById('formEliminarAlimento'),
            document.getElementById('formEliminarRegistro')
        ].filter(Boolean);

        forms.forEach(formulario => {
            guardarSnapshot(formulario);
            formulario.addEventListener('submit', async evento => {
                if (!['formCrearAlimento','formEditarAlimento','formRegistroComida','formEliminarAlimento','formEliminarRegistro'].includes(formulario.id)) return;
                evento.preventDefault();

                // Validar primero, igual que en los demás formularios del ERP.
                // La comprobación de cambios solo debe ejecutarse si los datos son válidos.
                if (formulario.id === 'formEditarAlimento') {
                    if (!validarFormulario(formulario)) {
                        evento.preventDefault();
                        window.showToast?.('warning', 'Revisa los campos marcados antes de actualizar.');
                        return;
                    }

                    if (sinCambios(formulario)) {
                        window.showToast?.('info', 'No hay cambios para guardar.');
                        return;
                    }
                }

                const submit = formulario.querySelector('[type="submit"]');
                if (submit?.disabled) return;
                if (submit) { submit.disabled = true; submit.dataset.textoOriginal = submit.innerHTML; }
                try {
                    const respuesta = await fetch(formulario.action, {
                        method: 'POST',
                        body: new FormData(formulario),
                        headers: {'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest'}
                    });
                    const resultado = await respuesta.json().catch(() => ({}));
                    if (respuesta.status === 422 && resultado.errors) {
                        Object.entries(resultado.errors).forEach(([campo, mensajes]) => {
                            const input = formulario.querySelector('[name="' + CSS.escape(campo) + '"]');
                            if (input) {
                                input.classList.add('is-invalid');
                                let feedback = input.parentElement.querySelector('.invalid-feedback');
                                if (!feedback) { feedback = document.createElement('div'); feedback.className = 'invalid-feedback js-campo-feedback'; input.insertAdjacentElement('afterend', feedback); }
                                feedback.textContent = mensajes[0];
                            }
                        });
                        window.showToast?.('warning', resultado.message || 'Revisa los campos marcados.');
                        return;
                    }
                    if (!respuesta.ok || !resultado.success) throw new Error(resultado.message || 'No fue posible guardar los cambios.');

                    if (resultado.alimento) actualizarFilaAlimento(resultado);
                    if (formulario.id === 'formRegistroComida' && resultado.registro) {
                        window.showToast?.('success', resultado.mensaje || 'Comida registrada correctamente.');
                        bootstrap.Modal.getInstance(formulario.closest('.modal'))?.hide();
                        // El registro alimentario modifica los totales y la tabla de la fecha; conserva la página y solicita actualizar la vista explícitamente.
                        document.dispatchEvent(new CustomEvent('alimentacion:registro-actualizado', {detail: resultado}));
                    } else if (formulario.id.startsWith('formEliminar')) {
                        const modal = formulario.closest('.modal');
                        bootstrap.Modal.getInstance(modal)?.hide();
                        const id = resultado.id;
                        const tabla = formulario.id === 'formEliminarAlimento' ? document.getElementById('tablaAlimentos') : document.getElementById('tablaRegistrosComida');
                        const tr = tabla?.querySelector('tbody tr[data-id="' + id + '"]');
                        if (tr && DataTable.isDataTable(tabla)) new DataTable.Api(tabla).row(tr).remove().draw(false);
                        else tr?.remove();
                        window.showToast?.('success', resultado.mensaje || 'Registro eliminado correctamente.');
                    } else {
                        window.showToast?.('success', resultado.mensaje || 'Cambios guardados correctamente.');
                        bootstrap.Modal.getInstance(formulario.closest('.modal'))?.hide();
                        guardarSnapshot(formulario);
                    }
                } catch (error) {
                    window.showToast?.('error', error.message || 'Ocurrió un error al guardar.');
                } finally {
                    if (submit) { submit.disabled = false; submit.innerHTML = submit.dataset.textoOriginal || submit.innerHTML; }
                }
            });
        });

        document.getElementById('modalEditarAlimento')?.addEventListener('shown.bs.modal', () => guardarSnapshot(document.getElementById('formEditarAlimento')));
    }

    inicializarEnviosAjax();

});

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
            // Los input[type="file"] no permiten asignar una ruta; solo pueden limpiarse con value = ''.
            if (campo.type !== 'file' && typeof campo.value === 'string') {
                campo.value = campo.value.trim();
            }
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
            .map(([key, value]) => [key, value instanceof File ? (value.name ? value.name + ':' + value.size + ':' + value.lastModified : '') : String(value).trim()])
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

    function crearTarjetaAlimento(alimento, catalogo) {
        const id = escapeHtml(alimento.id);
        const url = (catalogo.dataset.urlBase || '/alimentacion/alimentos') + '/' + encodeURIComponent(alimento.id);
        const imagen = alimento.imagen_url || '';
        const activo = Number(alimento.activo);
        const porcion = alimento.nombre_porcion ? '<small>' + escapeHtml(alimento.nombre_porcion) + ' · ' + escapeHtml(alimento.cantidad_porcion) + ' ' + escapeHtml(alimento.unidad_base) + '</small>' : '';
        const media = imagen ? '<img src="' + escapeHtml(imagen) + '" alt="' + escapeHtml(alimento.nombre) + '" loading="lazy">' : '<div class="alimento-card-placeholder" aria-label="Sin imagen"><i class="fa-solid fa-utensils"></i><span>Sin imagen</span></div>';
        const iconoEditar = decodificarIcono(catalogo.dataset.iconEditar, '<i class="fa-solid fa-pen"></i>');
        const iconoEliminar = decodificarIcono(catalogo.dataset.iconEliminar, '<i class="fa-solid fa-trash"></i>');
        const editar = catalogo.dataset.puedeEditar === '1' ? '<button type="button" class="btn btn-sm btn-outline-primary usuario-action-btn" data-bs-toggle="modal" data-bs-target="#modalEditarAlimento" data-id="' + id + '" data-nombre="' + escapeHtml(alimento.nombre) + '" data-categoria="' + escapeHtml(alimento.categoria || '') + '" data-unidad="' + escapeHtml(alimento.unidad_base || 'g') + '" data-nombre-porcion="' + escapeHtml(alimento.nombre_porcion || '') + '" data-cantidad-porcion="' + escapeHtml(alimento.cantidad_porcion ?? '') + '" data-calorias="' + escapeHtml(alimento.calorias) + '" data-proteinas="' + escapeHtml(alimento.proteinas) + '" data-carbohidratos="' + escapeHtml(alimento.carbohidratos) + '" data-grasas="' + escapeHtml(alimento.grasas) + '" data-activo="' + (activo ? '1' : '0') + '" data-imagen="' + escapeHtml(imagen) + '" data-url="' + escapeHtml(url) + '" data-tooltip="Editar alimento">' + iconoEditar + '<span>Editar</span></button>' : '';
        const eliminar = catalogo.dataset.puedeEliminar === '1' ? '<button type="button" class="btn btn-sm btn-outline-danger usuario-action-btn" data-bs-toggle="modal" data-bs-target="#modalEliminarAlimento" data-nombre="' + escapeHtml(alimento.nombre) + '" data-url="' + escapeHtml(url) + '" data-tooltip="Eliminar alimento">' + iconoEliminar + '<span>Eliminar</span></button>' : '';
        return '<article class="alimento-card ' + (activo ? '' : 'is-inactive') + '" data-id="' + id + '"><div class="alimento-card-media">' + media + '<span class="badge rounded-pill alimento-card-status ' + (activo ? 'text-bg-success' : 'text-bg-secondary') + '">' + (activo ? 'Activo' : 'Inactivo') + '</span></div><div class="alimento-card-body"><span class="alimento-card-category">' + escapeHtml(alimento.categoria || 'Alimento') + '</span><h3>' + escapeHtml(alimento.nombre) + '</h3><div class="alimento-card-portion"><i class="fa-solid fa-scale-balanced"></i><span>' + escapeHtml(String(alimento.unidad_base || 'g').toUpperCase()) + ' por cada 100 ' + escapeHtml(alimento.unidad_base || 'g') + '</span>' + porcion + '</div><div class="alimento-card-calories"><strong>' + Number(alimento.calorias || 0).toFixed(1) + '</strong><span>kcal <small>/ 100 ' + escapeHtml(alimento.unidad_base || 'g') + '</small></span></div><div class="alimento-card-macros"><div><span class="macro-dot macro-protein"></span><small>Proteínas</small><strong>' + Number(alimento.proteinas || 0).toFixed(1) + ' g</strong></div><div><span class="macro-dot macro-carbs"></span><small>Carbohidratos</small><strong>' + Number(alimento.carbohidratos || 0).toFixed(1) + ' g</strong></div><div><span class="macro-dot macro-fat"></span><small>Grasas</small><strong>' + Number(alimento.grasas || 0).toFixed(1) + ' g</strong></div></div>' + ((editar || eliminar) ? '<div class="alimento-card-actions">' + editar + eliminar + '</div>' : '') + '</div></article>';
    }

    function actualizarFilaAlimento(data) {
        const catalogo = document.getElementById('catalogoAlimentos');
        if (!catalogo || !data.alimento) return;
        const alimento = {...data.alimento, imagen_url: data.imagen_url ?? data.alimento.imagen_url ?? ''};
        const actual = catalogo.querySelector('.alimento-card[data-id="' + CSS.escape(String(alimento.id)) + '"]');
        const html = crearTarjetaAlimento(alimento, catalogo);
        if (actual) actual.outerHTML = html;
        else { catalogo.querySelector('.alimentos-empty')?.remove(); catalogo.insertAdjacentHTML('beforeend', html); }
        actualizarPaginacionAlimentos();
    }

    function decodificarIcono(valor, respaldo) {
        if (!valor) return respaldo;
        const temporal = document.createElement('textarea');
        temporal.innerHTML = valor;
        const icono = temporal.value;
        return icono.includes('<') ? icono : respaldo;
    }

    const ALIMENTOS_POR_PAGINA = 8;
    let paginaAlimentosActual = 1;

    function actualizarPaginacionAlimentos(resetear = false) {
        const catalogo = document.getElementById('catalogoAlimentos');
        if (!catalogo) return;
        const tarjetas = Array.from(catalogo.querySelectorAll('.alimento-card'));
        const paginas = Math.max(1, Math.ceil(tarjetas.length / ALIMENTOS_POR_PAGINA));
        if (resetear) paginaAlimentosActual = 1;
        paginaAlimentosActual = Math.min(paginaAlimentosActual, paginas);
        tarjetas.forEach((tarjeta, indice) => {
            tarjeta.style.display = indice >= (paginaAlimentosActual - 1) * ALIMENTOS_POR_PAGINA && indice < paginaAlimentosActual * ALIMENTOS_POR_PAGINA ? '' : 'none';
        });

        let navegacion = document.getElementById('paginacionAlimentos');
        if (!navegacion) {
            navegacion = document.createElement('nav');
            navegacion.id = 'paginacionAlimentos';
            navegacion.className = 'alimentos-paginacion';
            navegacion.setAttribute('aria-label', 'Paginación del catálogo de alimentos');
            catalogo.insertAdjacentElement('afterend', navegacion);
        }
        if (tarjetas.length <= ALIMENTOS_POR_PAGINA) {
            navegacion.innerHTML = '';
            navegacion.hidden = true;
            return;
        }
        navegacion.hidden = false;
        const inicio = (paginaAlimentosActual - 1) * ALIMENTOS_POR_PAGINA + 1;
        const fin = Math.min(paginaAlimentosActual * ALIMENTOS_POR_PAGINA, tarjetas.length);
        let botones = '<span class="alimentos-paginacion-info">Mostrando ' + inicio + '–' + fin + ' de ' + tarjetas.length + ' alimentos</span><div class="alimentos-paginacion-controles">';
        botones += '<button type="button" class="alimentos-pagina-btn" data-pagina="' + (paginaAlimentosActual - 1) + '" ' + (paginaAlimentosActual === 1 ? 'disabled' : '') + ' aria-label="Página anterior"><i class="fa-solid fa-chevron-left"></i></button>';
        for (let pagina = 1; pagina <= paginas; pagina++) {
            botones += '<button type="button" class="alimentos-pagina-btn ' + (pagina === paginaAlimentosActual ? 'active' : '') + '" data-pagina="' + pagina + '" aria-label="Página ' + pagina + '" ' + (pagina === paginaAlimentosActual ? 'aria-current="page"' : '') + '>' + pagina + '</button>';
        }
        botones += '<button type="button" class="alimentos-pagina-btn" data-pagina="' + (paginaAlimentosActual + 1) + '" ' + (paginaAlimentosActual === paginas ? 'disabled' : '') + ' aria-label="Página siguiente"><i class="fa-solid fa-chevron-right"></i></button></div>';
        navegacion.innerHTML = botones;
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
                        if (formulario.id === 'formEliminarAlimento') {
                            if (resultado.alimento) actualizarFilaAlimento(resultado);
                            else document.querySelector('.alimento-card[data-id="' + CSS.escape(String(id)) + '"]')?.remove();
                            actualizarPaginacionAlimentos();
                            const catalogo = document.getElementById('catalogoAlimentos');
                            if (catalogo && !catalogo.querySelector('.alimento-card') && !catalogo.querySelector('.alimentos-empty')) catalogo.insertAdjacentHTML('beforeend', '<div class="alimentos-empty"><div class="alimentos-empty-icon"><i class="fa-solid fa-carrot"></i></div><h3>Catálogo actualizado</h3><p>Agrega un alimento para volver a llenar tu catálogo nutricional.</p></div>');
                        } else {
                            const tabla = document.getElementById('tablaRegistrosComida');
                            const tr = tabla?.querySelector('tbody tr[data-id="' + id + '"]');
                            if (tr && DataTable.isDataTable(tabla)) new DataTable.Api(tabla).row(tr).remove().draw(false);
                            else tr?.remove();
                        }
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
    actualizarPaginacionAlimentos();

    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('.alimentos-pagina-btn');
        if (!boton || boton.disabled) return;
        paginaAlimentosActual = Number(boton.dataset.pagina);
        actualizarPaginacionAlimentos();
    });

});

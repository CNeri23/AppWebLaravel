document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.getElementById('tablaCategoriasFinancieras');
    if (!tabla) return;

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const acciones = Array.isArray(window.accionesCategoriasFinanzas) ? window.accionesCategoriasFinanzas : [];
    const modalNuevo = document.getElementById('modalCategoriaNueva');
    const modalEditar = document.getElementById('modalCategoriaEditar');
    const formNuevo = document.getElementById('formCategoriaNueva');
    const formEditar = document.getElementById('formCategoriaEditar');
    const filtroAmbito = document.getElementById('filtroAmbitoCategorias');
    let dataTable = null;
    let slimSelects = [];

    const tieneAccion = slug => acciones.some(accion => accion.slug === slug);
    const escapeHtml = valor => {
        const elemento = document.createElement('span');
        elemento.textContent = valor ?? '';
        return elemento.innerHTML;
    };

    function peticion(url, method, body) {
        return fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body
        }).then(async response => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok || data.success === false) {
                if (data.errors) {
                    const error = Object.values(data.errors).flat()[0];
                    throw new Error(error || 'Revisa los campos del formulario.');
                }
                throw new Error(data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
            }
            return data;
        });
    }

    function toast(tipo, mensaje) {
        if (typeof window.showToast === 'function') window.showToast(tipo, mensaje);
        else if (window.Swal) window.Swal.fire({ icon: tipo === 'success' ? 'success' : 'error', text: mensaje });
    }

    function validar(form) {
        let correcto = true;
        form.querySelectorAll('[required]').forEach(campo => {
            const valor = String(campo.value || '').trim();
            const invalido = !valor || !campo.checkValidity();
            campo.classList.toggle('is-invalid', invalido);
            campo.classList.toggle('is-valid', !invalido);
            if (invalido) correcto = false;
        });
        form.querySelectorAll('[maxlength]').forEach(campo => {
            const limite = Number(campo.maxLength);
            const invalido = limite > 0 && campo.value.length > limite;
            campo.classList.toggle('is-invalid', invalido);
            if (invalido) correcto = false;
        });
        return correcto;
    }

    function limpiar(form) {
        if (!form) return;
        form.reset();
        form.querySelectorAll('.is-valid, .is-invalid').forEach(campo => campo.classList.remove('is-valid', 'is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.style.display = '');
        if (window.SlimSelect) {
            form.querySelectorAll('.finanzas-select').forEach(select => {
                const instancia = slimSelects.find(item => item.select === select);
                if (instancia) instancia.instance.setSelected(select.value);
            });
        }
    }

    function cerrarModal(modal) {
        const instancia = modal ? bootstrap.Modal.getInstance(modal) : null;
        if (instancia) instancia.hide();
    }

    function mostrarError(error) {
        toast('error', error?.message || 'No se pudo completar la operación.');
    }

    function mostrarConfirmacionEliminar(nombre, callback) {
        if (!window.Swal) {
            if (window.confirm('¿Eliminar la categoría "' + nombre + '"?')) callback();
            return;
        }
        const estilo = getComputedStyle(document.body);
        const referencia = document.querySelector('.modal-content');
        const fondo = referencia ? getComputedStyle(referencia).backgroundColor : estilo.backgroundColor;
        window.Swal.fire({
            background: fondo,
            color: estilo.color,
            icon: 'warning',
            title: '¿Eliminar categoría?',
            text: 'Se eliminará "' + nombre + '". Esta acción no se puede deshacer.',
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Eliminar',
            cancelButtonText: 'Cancelar',
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            buttonsStyling: false,
            heightAuto: false,
            customClass: {
                popup: 'ip-confirmation-popup',
                icon: 'ip-confirmation-icon',
                title: 'ip-confirmation-title',
                actions: 'ip-confirmation-actions',
                confirmButton: 'btn btn-danger mx-1',
                cancelButton: 'btn btn-secondary mx-1'
            },
            didOpen: popup => {
                const contenedor = window.Swal.getContainer();
                contenedor?.addEventListener('click', evento => {
                    if (evento.target === contenedor) popup.animate([
                        { transform: 'translateX(0)' }, { transform: 'translateX(-8px)' },
                        { transform: 'translateX(8px)' }, { transform: 'translateX(-6px)' },
                        { transform: 'translateX(6px)' }, { transform: 'translateX(0)' }
                    ], { duration: 300, easing: 'ease-in-out' });
                }, true);
            }
        }).then(resultado => { if (resultado.isConfirmed) callback(); });
    }

    function botones(categoria) {
        let html = '<div class="finanzas-categoria-actions">';
        if (tieneAccion('finanzas.categorias.editar')) {
            html += '<button type="button" class="btn btn-outline-primary finanzas-action-btn btn-editar-categoria" data-id="' + categoria.id + '" data-tooltip="Editar categoría" aria-label="Editar categoría"><i class="fa-solid fa-pen-to-square"></i></button>';
        }
        if (tieneAccion('finanzas.categorias.eliminar')) {
            const disabled = Number(categoria.movimientos_count || 0) > 0 ? ' disabled' : '';
            html += '<button type="button" class="btn btn-outline-danger finanzas-action-btn btn-eliminar-categoria" data-id="' + categoria.id + '" data-name="' + escapeHtml(categoria.nombre) + '" data-tooltip="Eliminar categoría" aria-label="Eliminar categoría"' + disabled + '><i class="fa-regular fa-trash-can"></i></button>';
        }
        return html + '</div>';
    }

    function crearFila(categoria) {
        const fila = document.createElement('tr');
        fila.dataset.id = categoria.id;
        fila.dataset.nombre = categoria.nombre || '';
        fila.dataset.tipo = categoria.tipo || '';
        fila.dataset.descripcion = categoria.descripcion || '';
        fila.dataset.movimientos = categoria.movimientos_count || 0;
        fila.innerHTML = '<td class="fw-semibold">' + escapeHtml(categoria.nombre) + '</td>' +
            '<td><span class="badge rounded-pill ' + (categoria.tipo === 'ingreso' ? 'text-bg-success' : 'text-bg-danger') + '">' + (categoria.tipo === 'ingreso' ? 'Ingreso' : 'Gasto') + '</span></td>' +
            '<td class="text-secondary">' + (categoria.descripcion ? escapeHtml(categoria.descripcion) : 'Sin descripción') + '</td>' +
            '<td class="text-center">' + Number(categoria.movimientos_count || 0) + '</td><td>' + botones(categoria) + '</td>';
        return fila;
    }

    function actualizarFila(categoria) {
        if (!dataTable) return;
        dataTable.rows().every(function () {
            const fila = this.node();
            if (!fila || fila.dataset.id !== String(categoria.id)) return;
            const nueva = crearFila(categoria);
            this.data($(nueva).children().map(function () { return this.outerHTML; }).get());
            fila.dataset.nombre = categoria.nombre || '';
            fila.dataset.tipo = categoria.tipo || '';
            fila.dataset.descripcion = categoria.descripcion || '';
            fila.dataset.movimientos = categoria.movimientos_count || 0;
        });
        dataTable.draw(false);
    }

    function agregarFila(categoria) {
        if (dataTable) dataTable.row.add(crearFila(categoria)).draw(false);
    }

    function eliminarFila(id) {
        if (!dataTable) return;
        dataTable.rows().every(function () {
            if (this.node()?.dataset.id === String(id)) this.remove();
        });
        dataTable.draw(false);
    }

    function inicializarDataTable() {
        if (!window.jQuery || !jQuery.fn.DataTable) return;
        if (jQuery.fn.DataTable.isDataTable(tabla)) jQuery(tabla).DataTable().destroy();
        dataTable = jQuery(tabla).DataTable({
            language: { url: '/vendor/datatables/i18n/es-ES.json' },
            responsive: true,
            autoWidth: false,
            order: [[1, 'asc'], [0, 'asc']],
            columnDefs: [{ targets: 4, orderable: false, searchable: false }]
        });
    }

    function inicializarSlimSelects() {
        if (typeof window.SlimSelect !== 'function') return;
        document.querySelectorAll('.finanzas-select').forEach(select => {
            if (select.dataset.slimSelectInitialized === '1') return;
            const instance = new window.SlimSelect({ select });
            select.dataset.slimSelectInitialized = '1';
            slimSelects.push({ select, instance });
        });
    }

    formNuevo?.addEventListener('submit', evento => {
        evento.preventDefault();
        if (!validar(formNuevo)) {
            toast('warning', 'Completa correctamente los campos marcados.');
            return;
        }
        const boton = formNuevo.querySelector('[type="submit"]');
        const html = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        peticion(formNuevo.action, 'POST', new FormData(formNuevo))
            .then(data => {
                agregarFila(data.categoria);
                cerrarModal(modalNuevo);
                limpiar(formNuevo);
                toast('success', data.mensaje || 'Categoría creada correctamente.');
            })
            .catch(mostrarError)
            .finally(() => { boton.disabled = false; boton.innerHTML = html; });
    });

    formEditar?.addEventListener('submit', evento => {
        evento.preventDefault();
        if (!validar(formEditar)) {
            toast('warning', 'Completa correctamente los campos marcados.');
            return;
        }
        const boton = formEditar.querySelector('[type="submit"]');
        const html = boton.innerHTML;
        boton.disabled = true;
        boton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
        peticion(formEditar.action, 'POST', new FormData(formEditar))
            .then(data => {
                actualizarFila(data.categoria);
                cerrarModal(modalEditar);
                toast('success', data.mensaje || 'Categoría actualizada correctamente.');
            })
            .catch(mostrarError)
            .finally(() => { boton.disabled = false; boton.innerHTML = html; });
    });

    tabla.addEventListener('click', evento => {
        const editar = evento.target.closest('.btn-editar-categoria');
        const eliminar = evento.target.closest('.btn-eliminar-categoria');
        if (editar) {
            const fila = editar.closest('tr');
            if (!fila) return;
            formEditar.action = '/finanzas/categorias/' + encodeURIComponent(fila.dataset.id);
            formEditar.querySelector('[name="nombre"]').value = fila.dataset.nombre || '';
            formEditar.querySelector('[name="tipo"]').value = fila.dataset.tipo || 'gasto';
            formEditar.querySelector('[name="descripcion"]').value = fila.dataset.descripcion || '';
            formEditar.querySelectorAll('.is-valid, .is-invalid').forEach(campo => campo.classList.remove('is-valid', 'is-invalid'));
            slimSelects.forEach(item => { if (formEditar.contains(item.select)) item.instance.setSelected(fila.dataset.tipo || 'gasto'); });
            bootstrap.Modal.getOrCreateInstance(modalEditar).show();
        }
        if (eliminar) {
            if (eliminar.disabled) return;
            const id = eliminar.dataset.id;
            mostrarConfirmacionEliminar(eliminar.dataset.name || '', () => {
                eliminar.disabled = true;
                const datos = new FormData();
                datos.append('_method', 'DELETE');
                peticion('/finanzas/categorias/' + encodeURIComponent(id), 'POST', datos)
                    .then(data => { eliminarFila(data.id); toast('success', data.mensaje || 'Categoría eliminada correctamente.'); })
                    .catch(mostrarError)
                    .finally(() => { eliminar.disabled = false; });
            });
        }
    });

    filtroAmbito?.addEventListener('change', () => {
        const url = new URL(window.location.href);
        url.searchParams.set('ambito', filtroAmbito.value);
        if (window.navigateTo) window.navigateTo(url.toString());
        else window.location.href = url.toString();
    });

    modalNuevo?.addEventListener('hidden.bs.modal', () => limpiar(formNuevo));
    inicializarSlimSelects();
    inicializarDataTable();
});

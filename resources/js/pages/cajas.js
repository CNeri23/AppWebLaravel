document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let tablaCajas = null;

    const modalNuevaCajaEl = document.getElementById('modalNuevaCaja');
    const modalEditarCajaEl = document.getElementById('modalEditarCaja');
    const modalEstadoCajaEl = document.getElementById('modalEstadoCaja');

    const modalNuevaCaja = modalNuevaCajaEl
        ? new bootstrap.Modal(modalNuevaCajaEl)
        : null;

    const modalEditarCaja = modalEditarCajaEl
        ? new bootstrap.Modal(modalEditarCajaEl)
        : null;

    const modalEstadoCaja = modalEstadoCajaEl
        ? new bootstrap.Modal(modalEstadoCajaEl)
        : null;

    const formNuevaCaja = document.getElementById('formNuevaCaja');
    const formEditarCaja = document.getElementById('formEditarCaja');
    const formEstadoCaja = document.getElementById('formEstadoCaja');

    function peticion(url, method, body = null) {
        const opciones = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        if (body instanceof FormData) {
            opciones.body = body;
        } else if (body) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = body;
        }

        return fetch(url, opciones).then(async (response) => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                if (data.errors) {
                    const primerError = Object.values(data.errors).flat()[0];

                    throw new Error(
                        primerError ||
                        data.mensaje ||
                        data.message ||
                        'Ocurrió un error al procesar la solicitud.'
                    );
                }

                throw new Error(
                    data.mensaje ||
                    data.message ||
                    'Ocurrió un error al procesar la solicitud.'
                );
            }

            return data;
        });
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.hide();
        }
    }

    function mostrarError(error) {
        window.showToast(
            'error',
            error.message || 'Ocurrió un error al procesar la solicitud.'
        );
    }

    function mostrarInfo(mensaje) {
        window.showToast('info', mensaje);
    }

    function escapeHtml(valor) {
        const div = document.createElement('div');
        div.textContent = valor ?? '';
        return div.innerHTML;
    }

    function escapeAttribute(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function obtenerAccionesCajas() {
        return Array.isArray(window.accionesCajas)
            ? window.accionesCajas
            : [];
    }

    function tieneAccion(slug) {
        return obtenerAccionesCajas().some(function (accion) {
            return accion.slug === slug;
        });
    }

    function obtenerSesionAbierta(caja) {
        if (caja.sesion_abierta) {
            return caja.sesion_abierta;
        }

        if (caja.sesionAbierta) {
            return caja.sesionAbierta;
        }

        if (Array.isArray(caja.sesiones)) {
            return caja.sesiones.find(function (sesion) {
                return sesion.estado === 'abierta';
            }) || null;
        }

        return null;
    }

    function obtenerUsuarioSesion(sesion) {
        if (!sesion) {
            return '';
        }

        if (sesion.usuario_apertura && sesion.usuario_apertura.name) {
            return sesion.usuario_apertura.name;
        }

        if (sesion.usuarioApertura && sesion.usuarioApertura.name) {
            return sesion.usuarioApertura.name;
        }

        return '';
    }

    function cambiarEstadoCaja(boton, activar) {
        if (boton.disabled) {
            return;
        }

        const url = boton.dataset.url;
        const id = boton.dataset.id;
        boton.disabled = true;
        boton.classList.add('procesando');

        const cuerpo = new FormData();
        cuerpo.append('_method', 'PATCH');
        cuerpo.append('activo', activar ? '1' : '0');

        peticion(url, 'POST', cuerpo)
            .then(function (data) {
                window.showToast(
                    'success',
                    data.mensaje || 'Estado de la caja actualizado correctamente.'
                );

                actualizarCajaEnTabla(data.caja);
            })
            .catch(function (error) {
                mostrarError(error);
                boton.disabled = false;
                boton.classList.remove('procesando');
            });
    }

    function crearAccionesCaja(caja) {
        let html = '<div class="caja-actions">';

        obtenerAccionesCajas().forEach(function (accion) {

            if (accion.slug === 'cajas.editar') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-primary caja-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEditarCaja" ' +
                    'data-id="' + escapeAttribute(caja.id) + '" ' +
                    'data-nombre="' + escapeAttribute(caja.nombre) + '" ' +
                    'data-descripcion="' + escapeAttribute(caja.descripcion || '') + '" ' +
                    'data-url="/cajas/' + escapeAttribute(caja.id) + '">' +
                    (accion.icono || '<i class="fa-solid fa-pen"></i>') +
                    '</button>';
            }

            if (accion.slug === 'cajas.ver') {
                html +=
                    '<a href="/sesiones-caja?caja_id=' +
                    encodeURIComponent(caja.id) + '" ' +
                    'class="btn btn-sm btn-outline-info caja-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '">' +
                    (accion.icono || '<i class="fa-regular fa-eye"></i>') +
                    '</a>';
            }
        });

        if (tieneAccion('cajas.editar')) {
            html +=
                '<button type="button" ' +
                'class="usuario-toggle-btn caja-toggle-btn ' +
                (caja.activo ? 'activo' : 'inactivo') + '" ' +
                'title="' + (caja.activo ? 'Desactivar caja' : 'Activar caja') + '" ' +
                'data-tooltip="' + (caja.activo ? 'Desactivar caja' : 'Activar caja') + '" ' +
                'aria-label="' + (caja.activo ? 'Desactivar caja' : 'Activar caja') + '" ' +
                'aria-pressed="' + (caja.activo ? 'true' : 'false') + '" ' +
                'data-id="' + escapeAttribute(caja.id) + '" ' +
                'data-name="' + escapeAttribute(caja.nombre) + '" ' +
                'data-activo="' + (caja.activo ? '1' : '0') + '" ' +
                'data-url="/cajas/' + escapeAttribute(caja.id) + '/estado">' +
                '<span class="usuario-toggle-track">' +
                '<span class="usuario-toggle-thumb"></span>' +
                '</span>' +
                '</button>';
        }

        html += '</div>';

        return html;
    }

    const tablaCajasEl = document.querySelector('#tablaCajas');

    if (tablaCajasEl) {
        tablaCajasEl.addEventListener('click', function (event) {
            const boton = event.target.closest('.caja-toggle-btn');

            if (!boton || boton.disabled) {
                return;
            }

            const activar = boton.dataset.activo !== '1';

            if (activar) {
                cambiarEstadoCaja(boton, true);
                return;
            }

            if (!window.Swal) {
                if (window.confirm('¿Desactivar caja?')) {
                    cambiarEstadoCaja(boton, false);
                }
                return;
            }

            const cuerpo = getComputedStyle(document.body);
            const referencia = document.querySelector('.modal-content');
            let fondo = referencia
                ? getComputedStyle(referencia).backgroundColor
                : cuerpo.backgroundColor;

            if (!fondo || fondo === 'transparent' || fondo === 'rgba(0, 0, 0, 0)') {
                fondo = cuerpo.backgroundColor;
            }

            Swal.fire({
                background: fondo,
                color: cuerpo.color,
                icon: 'warning',
                title: '¿Desactivar caja?',
                text: 'La caja "' + (boton.dataset.name || '') + '" quedará inactiva.',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Desactivar',
                cancelButtonText: 'Cancelar',
                allowOutsideClick: false,
                allowEscapeKey: false,
                allowEnterKey: false,
                buttonsStyling: false,
                heightAuto: false,
                customClass: {
                    confirmButton: 'btn btn-danger mx-1',
                    cancelButton: 'btn btn-secondary mx-1'
                },
                didOpen: function (popup) {
                    const contenedor = Swal.getContainer();

                    if (contenedor) {
                        contenedor.addEventListener('click', function (evento) {
                            if (evento.target === contenedor) {
                                popup.animate(
                                    [
                                        { transform: 'translateX(0)' },
                                        { transform: 'translateX(-8px)' },
                                        { transform: 'translateX(8px)' },
                                        { transform: 'translateX(-6px)' },
                                        { transform: 'translateX(6px)' },
                                        { transform: 'translateX(0)' }
                                    ],
                                    { duration: 300, easing: 'ease-in-out' }
                                );
                            }
                        }, true);
                    }
                }
            }).then(function (resultado) {
                if (resultado.isConfirmed) {
                    cambiarEstadoCaja(boton, false);
                }
            });
        });
    }

    function crearFilaCaja(caja) {
        const fila = document.createElement('tr');

        const sesion = obtenerSesionAbierta(caja);
        const usuario = obtenerUsuarioSesion(sesion);
        const descripcion = caja.descripcion || 'Sin descripción';
        const estado = caja.activo ? '<span class="badge text-bg-success">Activa</span>' : '<span class="badge text-bg-secondary">Inactiva</span>';
        const sesionTexto = sesion ? '<span class="badge text-bg-primary">Abierta</span>' : '<span class="text-secondary">Sin sesión</span>';
        const usuarioTexto = usuario ? escapeHtml(usuario) : '<span class="text-secondary">—</span>';

        fila.innerHTML = `
            <td>
                <div class="fw-semibold">
                    ${escapeHtml(caja.nombre)}
                </div>
            </td>

            <td>
                <span class="text-secondary">
                    ${escapeHtml(descripcion)}
                </span>
            </td>

            <td>
                ${estado}
            </td>

            <td>
                ${sesionTexto}
            </td>

            <td>
                ${usuarioTexto}
            </td>

            <td class="text-center px-4">
                ${crearAccionesCaja(caja)}
            </td>
        `;

        return fila;
    }

    if (document.querySelector('#tablaCajas')) {
        tablaCajas = new DataTable('#tablaCajas', {
            autoWidth: false,

            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron cajas',
                emptyTable: 'No hay cajas registradas',

                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            },

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],

            order: [
                [0, 'asc']
            ],

            columnDefs: [
                {
                    orderable: false,
                    searchable: false,
                    targets: 5,
                    width: '18%'
                }
            ],

            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging'
            },

            initComplete: function () {
                configurarTooltipsPaginacion();
            },

            drawCallback: function () {
                configurarTooltipsPaginacion();
            }
        });

        function configurarTooltipsPaginacion() {
            const paginacion = document.querySelector(
                '#tablaLogs_wrapper .dt-paging'
            );

            if (!paginacion) {
                return;
            }

            const botones = paginacion.querySelectorAll('button');

            botones.forEach(function (button) {
                const icono = button.querySelector('i');

                if (!icono) {
                    return;
                }

                if (icono.classList.contains('fa-angles-left')) {
                    button.setAttribute('title', 'Primera página');
                    button.setAttribute('aria-label', 'Primera página');
                }

                if (icono.classList.contains('fa-angle-left')) {
                    button.setAttribute('title', 'Página anterior');
                    button.setAttribute('aria-label', 'Página anterior');
                }

                if (icono.classList.contains('fa-angle-right')) {
                    button.setAttribute('title', 'Página siguiente');
                    button.setAttribute('aria-label', 'Página siguiente');
                }

                if (icono.classList.contains('fa-angles-right')) {
                    button.setAttribute('title', 'Última página');
                    button.setAttribute('aria-label', 'Última página');
                }
            });
        }
    }

    function ajustarFila(fila) {
        if (!fila) {
            return;
        }
        const celdas = fila.children;

        if (celdas.length < 6) {
            return;
        }
        celdas[0].style.width = '17%';
        celdas[1].style.width = '25%';
        celdas[2].style.width = '12%';
        celdas[3].style.width = '14%';
        celdas[4].style.width = '14%';
        celdas[5].style.width = '18%';
        celdas[5].classList.remove('text-end');
        celdas[5].classList.add('text-center', 'px-4');
    }

    function ajustarTodasLasFilas() {
        if (!tablaCajas) {
            return;
        }

        tablaCajas.rows().every(function () {
            ajustarFila(this.node());
        });
    }

    function agregarCajaATabla(caja) {
        if (!tablaCajas || !caja) {
            return;
        }

        tablaCajas.row.add(crearFilaCaja(caja)).draw(false);

        ajustarTodasLasFilas();
        tablaCajas.columns.adjust();
    }

    function encontrarFilaCaja(id) {
        if (!tablaCajas) {
            return null;
        }

        let filaEncontrada = null;

        tablaCajas.rows().every(function () {
            const fila = this.node();

            if (!fila || filaEncontrada) {
                return;
            }

            const boton = fila.querySelector(
                '.caja-action-btn[data-id="' + id + '"]'
            );

            if (boton) {
                filaEncontrada = this;
            }
        });

        return filaEncontrada;
    }

    function actualizarCajaEnTabla(caja) {
        if (!tablaCajas || !caja) {
            return;
        }

        const fila = encontrarFilaCaja(caja.id);

        if (!fila) {
            return;
        }

        const nuevaFila = crearFilaCaja(caja);
        const datos = fila.data();

        for (let i = 0; i < 6; i++) {
            datos[i] = nuevaFila.children[i].innerHTML;
        }

        fila.data(datos);

        tablaCajas.draw(false);

        ajustarTodasLasFilas();
        tablaCajas.columns.adjust();
    }

    if (modalEditarCajaEl) {
        modalEditarCajaEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const nombre = button.dataset.nombre || '';
            const descripcion = button.dataset.descripcion || '';
            const url = button.dataset.url || `/cajas/${id}`;

            document.getElementById('editar_caja_id').value = id;
            document.getElementById('editar_nombre').value = nombre;
            document.getElementById('editar_descripcion').value = descripcion;

            formEditarCaja.action = url;
            formEditarCaja.dataset.nombreOriginal = nombre.trim();
            formEditarCaja.dataset.descripcionOriginal = descripcion.trim();
        });
    }

    if (modalEstadoCajaEl) {
        modalEstadoCajaEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const nombre = button.dataset.nombre || '';
            const activo = button.dataset.activo === '1';
            const url = button.dataset.url || `/cajas/${id}/estado`;

            formEstadoCaja.action = url;
            formEstadoCaja.dataset.cajaId = id;

            document.getElementById('estado_caja_id').value = id;
            document.getElementById('estado_caja_nombre').textContent = nombre;

            const titulo = document.getElementById('estadoCajaTitulo');
            const icono = document.getElementById('estadoCajaIcono');
            const boton = document.getElementById('btnEstadoCaja');

            if (activo) {
                titulo.textContent = '¿Desactivar caja?';
                icono.className = 'mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center';
                icono.innerHTML = '<i class="fa-solid fa-toggle-off fa-lg"></i>';
            } else {
                titulo.textContent = '¿Activar caja?';
                icono.className = 'mx-auto mb-3 rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center';
                icono.innerHTML = '<i class="fa-solid fa-toggle-on fa-lg"></i>';
            }
            boton.className = 'btn btn-primary';
            boton.innerHTML ='<i class="fa-solid fa-floppy-disk me-2"></i>' +'Guardar';
        });

        modalEstadoCajaEl.addEventListener('hidden.bs.modal', function () {
            formEstadoCaja.action = '';
            formEstadoCaja.dataset.cajaId = '';

            document.getElementById('estado_caja_id').value = '';
        });
    }

    function enviarFormulario(form) {
        const botonSubmit = form.querySelector('button[type="submit"]');

        const textoOriginal = botonSubmit ? botonSubmit.innerHTML : '';

        if (botonSubmit) {
            botonSubmit.disabled = true;

            botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 'Guardando...';
        }

        return peticion(form.action, 'POST', new FormData(form))
            .finally(function () {
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                }
            });
    }

    if (formNuevaCaja) {
        formNuevaCaja.addEventListener('submit', function (event) {
            event.preventDefault();

            enviarFormulario(formNuevaCaja)
                .then(function (data) {
                    cerrarModal(modalNuevaCaja);
                    
                    window.showToast( 'success', data.mensaje || 'Caja creada correctamente.');
                    agregarCajaATabla(data.caja);
                    formNuevaCaja.reset();
                })
                .catch(mostrarError);
        });
    }

    if (formEditarCaja) {
        formEditarCaja.addEventListener('submit', function (event) {
            event.preventDefault();

            const inputNombre = document.getElementById('editar_nombre');
            const inputDescripcion = document.getElementById('editar_descripcion');

            const nombre = inputNombre.value.trim();
            const descripcion = inputDescripcion.value.trim();

            if (!nombre) {
                window.showToast('error', 'El nombre de la caja es obligatorio.');
                inputNombre.focus();
                return;
            }

            if (nombre === (formEditarCaja.dataset.nombreOriginal || '') && descripcion === (formEditarCaja.dataset.descripcionOriginal || '')) {
                mostrarInfo('No hubo cambios para actualizar.');
                return;
            }

            enviarFormulario(formEditarCaja)
                .then(function (data) {
                    if (data.sin_cambios) {
                        mostrarInfo(data.mensaje || 'No hubo cambios para actualizar.');
                        return;
                    }
                    cerrarModal(modalEditarCaja);

                    window.showToast('success', data.mensaje || 'Caja actualizada correctamente.');
                    actualizarCajaEnTabla(data.caja);
                })
                .catch(mostrarError);
        });
    }

    if (formEstadoCaja) {
        formEstadoCaja.addEventListener('submit', function (event) {
            event.preventDefault();

            const id = document.getElementById('estado_caja_id').value;

            if (!id) {
                window.showToast('error', 'No se pudo identificar la caja.');
                return;
            }

            if (!formEstadoCaja.action || !formEstadoCaja.action.match(/\/cajas\/[^/]+\/estado$/)) {
                formEstadoCaja.action = `/cajas/${id}/estado`;
            }

            const botonSubmit = document.getElementById('btnEstadoCaja');
            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;
            botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 'Guardando...';

            peticion(formEstadoCaja.action, 'POST', new FormData(formEstadoCaja))
                .then(function (data) {
                    cerrarModal(modalEstadoCaja);

                    window.showToast('success', data.mensaje || 'Estado de la caja actualizado correctamente.');

                    actualizarCajaEnTabla(data.caja);
                })
                .catch(mostrarError)
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });
    }

    ajustarTodasLasFilas();

    if (tablaCajas) {
        tablaCajas.columns.adjust();
    }
});
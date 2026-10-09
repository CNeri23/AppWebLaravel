document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
    let tablaDirecciones = null;

    const modalNuevaDireccionEl = document.getElementById('modalNuevaDireccion');
    const modalEditarDireccionEl = document.getElementById('modalEditarDireccion');
    const modalEliminarDireccionEl = document.getElementById('modalEliminarDireccion');
    const modalNuevaDireccion = modalNuevaDireccionEl ? new bootstrap.Modal(modalNuevaDireccionEl) : null;
    const modalEditarDireccion = modalEditarDireccionEl ? new bootstrap.Modal(modalEditarDireccionEl) : null;
    const modalEliminarDireccion = modalEliminarDireccionEl ? new bootstrap.Modal(modalEliminarDireccionEl) : null;

    function peticion(url, method, body = null) {
        const opciones = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
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

                    throw new Error(primerError || data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
                }

                throw new Error(data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
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
            error.message
        );
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

    function descripcionDireccion(direccion) {
        let texto = direccion.calle + ' ' + direccion.numero_exterior;

        if (direccion.numero_interior) {
            texto += ' Int. ' +  direccion.numero_interior;
        }

        texto +=', ' + direccion.colonia;
        return texto;
    }

    function crearAccionesDireccion(direccion, urls) {
        let html = '<div class="direccion-actions">';
        const acciones = window.accionesDirecciones || [];
        acciones.forEach(function (accion) {
            if (accion.slug === 'direcciones.editar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-primary direccion-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEditarDireccion" ' +
                    'data-id="' + escapeAttribute(direccion.id) + '" ' +
                    'data-calle="' + escapeAttribute(direccion.calle) + '" ' +
                    'data-numero-exterior="' + escapeAttribute(direccion.numero_exterior) + '" ' +
                    'data-numero-interior="' + escapeAttribute(direccion.numero_interior) + '" ' +
                    'data-colonia="' + escapeAttribute(direccion.colonia) + '" ' +
                    'data-codigo-postal="' + escapeAttribute(direccion.codigo_postal) + '" ' +
                    'data-municipio="' + escapeAttribute(direccion.municipio) + '" ' +
                    'data-estado="' + escapeAttribute(direccion.estado) + '" ' +
                    'data-pais="' + escapeAttribute(direccion.pais) + '" ' +
                    'data-personas-count="' + escapeAttribute(direccion.personas_count) + '" ' +
                    'data-url="' + escapeAttribute(urls.update) + '">' +
                    (accion.icono || '<i class="fa-solid fa-pen"></i>') +
                    '</button>';
            }

            else if (accion.slug === 'direcciones.eliminar') {

                const personasAsignadas = Number(direccion.personas_count || 0);
                const disabled = personasAsignadas > 0 ? ' disabled' : '';
                const titulo = personasAsignadas > 0 ? 'No se puede eliminar: dirección asignada' : accion.nombre;

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger direccion-action-btn" ' +
                    'title="' + escapeAttribute(titulo) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEliminarDireccion" ' +
                    'data-id="' + escapeAttribute(direccion.id) + '" ' +
                    'data-name="' + escapeAttribute(descripcionDireccion(direccion)) + '" ' +
                    'data-personas-count="' + escapeAttribute(personasAsignadas) + '" ' +
                    'data-url="' + escapeAttribute(urls.delete) + '"' +
                    disabled + '>' +
                    (accion.icono || '<i class="fa-solid fa-trash"></i>') +
                    '</button>';
            }
        });

        html += '</div>';
        return html;
    }

    function crearFilaDireccion(direccion, urls = null) {
        const fila = document.createElement('tr');
        const urlsDireccion = urls || {update: `/direcciones/${direccion.id}`, delete: `/direcciones/${direccion.id}`};
        const personasAsignadas = Number(direccion.personas_count || 0);
        fila.innerHTML = `
            <td>${escapeHtml(direccion.calle)}</td>
            <td>${escapeHtml(direccion.numero_exterior)}</td>
            <td>${escapeHtml(direccion.numero_interior || '—')}</td>
            <td>${escapeHtml(direccion.colonia)}</td>
            <td>${escapeHtml(direccion.codigo_postal)}</td>
            <td>${escapeHtml(direccion.municipio)}</td>
            <td>${escapeHtml(direccion.estado)}</td>
            <td>${escapeHtml(direccion.pais)}</td>
            <td class="text-center">${personasAsignadas > 0 ? '<span class="badge text-bg-primary">' + personasAsignadas + '</span>' : '<span class="badge text-bg-secondary">0</span>' }</td>
            <td class="text-end px-4">${crearAccionesDireccion(direccion, urlsDireccion)}</td>
        `;
        return fila;
    }

    if (document.querySelector('#tablaDirecciones')) {
        tablaDirecciones = new DataTable(
            '#tablaDirecciones',
            {
                autoWidth: false,

                language: {
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    zeroRecords: 'No se encontraron direcciones',
                    emptyTable: 'No hay direcciones registradas',

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
                        targets: 9,
                        width: '10%'
                    },

                    {
                        targets: 8,
                        className: 'text-center',
                        width: '8%'
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
            }
        );
    }

    function configurarTooltipsPaginacion() {
        const paginacion = document.querySelector('#tablaLogs_wrapper .dt-paging');

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

    function ajustarFila(fila) {
        if (!fila) {
            return;
        }

        const celdas = fila.children;

        if (celdas.length < 10) {
            return;
        }

        celdas[0].style.width = '18%';
        celdas[1].style.width = '9%';
        celdas[2].style.width = '9%';
        celdas[3].style.width = '15%';
        celdas[4].style.width = '7%';
        celdas[5].style.width = '14%';
        celdas[6].style.width = '12%';
        celdas[7].style.width = '9%';
        celdas[8].style.width = '7%';
        celdas[9].style.width = '10%';

        celdas[8].classList.add(
            'text-center'
        );

        celdas[9].classList.add(
            'text-end',
            'px-4'
        );
    }

    function ajustarTodasLasFilas() {
        if (!tablaDirecciones) {
            return;
        }

        tablaDirecciones
            .rows()
            .every(function () {
                ajustarFila(
                    this.node()
                );
            });
    }

    function agregarDireccionATabla(direccion, urls) {
        if (!tablaDirecciones) {
            return;
        }
        const fila = crearFilaDireccion(direccion, urls );
        tablaDirecciones.row.add(fila).draw(false);
        ajustarTodasLasFilas();
        tablaDirecciones.columns.adjust();
    }

    function actualizarDireccionEnTabla(direccion, urls) {
        if (!tablaDirecciones) {
            return;
        }

        tablaDirecciones
            .rows()
            .every(function () {

                const fila = this.node();

                if (!fila) {
                    return;
                }

                const boton = fila.querySelector('.direccion-action-btn');

                if (boton && boton.dataset.id === String(direccion.id)
                ) {
                    const datos = this.data();

                    datos[0] = escapeHtml(direccion.calle);
                    datos[1] = escapeHtml(direccion.numero_exterior);
                    datos[2] = escapeHtml(direccion.numero_interior || '—');
                    datos[3] = escapeHtml(direccion.colonia);
                    datos[4] = escapeHtml(direccion.codigo_postal);
                    datos[5] = escapeHtml(direccion.municipio);
                    datos[6] = escapeHtml(direccion.estado);
                    datos[7] = escapeHtml(direccion.pais);

                    const personasAsignadas = Number(direccion.personas_count || 0);
                    datos[8] = personasAsignadas > 0 ? '<span class="badge text-bg-primary">' + personasAsignadas + '</span>' : '<span class="badge text-bg-secondary">0</span>';
                    datos[9] = crearAccionesDireccion(direccion, urls);

                    this.data(datos);
                }
            });

        tablaDirecciones.draw(false);
        ajustarTodasLasFilas();
        tablaDirecciones.columns.adjust();
    }

    function eliminarDireccionDeTabla(id) {
        if (!tablaDirecciones) {
            return;
        }

        tablaDirecciones
            .rows()
            .every(function () {

                const fila = this.node();

                if (!fila) {
                    return;
                }

                const boton = fila.querySelector('.direccion-action-btn');

                if (boton && boton.dataset.id ===  String(id)) {
                    this.remove();
                }
            });

        tablaDirecciones.draw(false);
        ajustarTodasLasFilas();
    }

    let datosOriginalesEditar = null;

    const MENSAJE_SIN_CAMBIOS = 'No hubo cambios para actualizar.';
    const modalEditar = document.getElementById('modalEditarDireccion');

    if (modalEditar) {
        modalEditar.addEventListener(
            'show.bs.modal',
            function (event) {

                const button = event.relatedTarget;

                datosOriginalesEditar = null;

                if (!button) {
                    return;
                }

                datosOriginalesEditar = {
                    calle: (button.dataset.calle || '').trim(),
                    numero_exterior: (button.dataset.numeroExterior || '').trim(),
                    numero_interior: (button.dataset.numeroInterior || '').trim(),
                    colonia: (button.dataset.colonia || '').trim(),
                    codigo_postal: (button.dataset.codigoPostal || '').trim(),
                    municipio: (button.dataset.municipio || '').trim(),
                    estado: (button.dataset.estado || '').trim(),
                    pais: (button.dataset.pais || '').trim()
                };

                document.getElementById('editar_id').value = button.dataset.id;
                document.getElementById('editar_calle').value = button.dataset.calle || '';
                document.getElementById('editar_numero_exterior').value = button.dataset.numeroExterior || '';
                document.getElementById('editar_numero_interior').value = button.dataset.numeroInterior || '';
                document.getElementById('editar_colonia').value = button.dataset.colonia || '';
                document.getElementById('editar_codigo_postal').value = button.dataset.codigoPostal || '';
                document.getElementById('editar_municipio').value = button.dataset.municipio || '';
                document.getElementById('editar_estado').value = button.dataset.estado || '';
                document.getElementById('editar_pais').value = button.dataset.pais || '';
                document.getElementById('formEditarDireccion').setAttribute('action', button.dataset.url);
            }
        );
    }

    const modalEliminar = document.getElementById('modalEliminarDireccion');

    if (modalEliminar) {
        modalEliminar.addEventListener(
            'show.bs.modal',
            function (event) {
                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                const personasAsignadas = Number(button.dataset.personasCount || 0);

                if (personasAsignadas > 0) {
                    event.preventDefault();

                    cerrarModal(
                        modalEliminarDireccion
                    );

                    window.showToast(
                        'warning',
                        'No se puede eliminar esta dirección porque está asignada a ' +
                        personasAsignadas +
                        (
                            personasAsignadas === 1
                                ? ' persona.'
                                : ' personas.'
                        )
                    );

                    return;
                }

                document.getElementById('eliminar_id').value = button.dataset.id;
                document.getElementById('eliminar_nombre').textContent = button.dataset.name;
                document.getElementById('formEliminarDireccion').setAttribute('action',button.dataset.url);
            }
        );
    }

    function enviarFormulario(form, modal, mensajePorDefecto) {
        const botonSubmit = form.querySelector('button[type="submit"]');
        const textoOriginal = botonSubmit?.innerHTML;

        if (botonSubmit) {
            botonSubmit.disabled = true;
            botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 'Guardando...';
        }

        const formData = new FormData(form);

        return peticion(form.action,'POST', formData)
            .then((data) => {
                cerrarModal(modal);
                window.showToast('success', data.mensaje || mensajePorDefecto);
                return data;
            })
            .finally(() => {
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                }
            });
    }

    const formNuevaDireccion = document.getElementById('formNuevaDireccion');

    if (formNuevaDireccion) {
        formNuevaDireccion.addEventListener('submit',
            function (event) {

                event.preventDefault();

                enviarFormulario(formNuevaDireccion, modalNuevaDireccion, 'Dirección creada correctamente.')
                    .then((data) => {
                        agregarDireccionATabla(data.direccion, data.urls);
                        formNuevaDireccion.reset();
                        const pais = document.getElementById('pais');

                        if (pais) {pais.value = 'México';}
                    })
                    .catch(mostrarError);
            }
        );
    }

    const formEditarDireccion = document.getElementById('formEditarDireccion');

    if (formEditarDireccion) {
        formEditarDireccion.addEventListener('submit',
            function (event) {

                event.preventDefault();

                const campos =
                    [
                        'calle',
                        'numero_exterior',
                        'numero_interior',
                        'colonia',
                        'codigo_postal',
                        'municipio',
                        'estado',
                        'pais'
                    ];

                const valoresActuales = {};

                campos.forEach(function (campo) {

                    const input = document.getElementById('editar_' + campo);
                    valoresActuales[campo] = input ? input.value.trim() : '';
                });

                const sinCambios = datosOriginalesEditar && campos.every(function (campo) {
                    return (valoresActuales[campo] === datosOriginalesEditar[campo]);
                });

                if (sinCambios) {
                    window.showToast('info', MENSAJE_SIN_CAMBIOS);
                    return;
                }

                enviarFormulario(formEditarDireccion, modalEditarDireccion, 'Dirección actualizada correctamente.')
                    .then((data) => {
                        actualizarDireccionEnTabla(
                            data.direccion,
                            {
                                update: `/direcciones/${data.direccion.id}`,
                                delete: `/direcciones/${data.direccion.id}`,
                            }
                        );
                    })
                    .catch(mostrarError);
            }
        );
    }

    const formEliminarDireccion = document.getElementById('formEliminarDireccion');

    if (formEliminarDireccion) {
        formEliminarDireccion.addEventListener('submit',
            function (event) {
                event.preventDefault();
                const id = document.getElementById('eliminar_id').value;
                enviarFormulario(formEliminarDireccion, modalEliminarDireccion, 'Dirección eliminada correctamente.')
                    .then((data) => {
                        eliminarDireccionDeTabla(data.id || id);
                    })
                    .catch(mostrarError);
            }
        );
    }
    ajustarTodasLasFilas();
});

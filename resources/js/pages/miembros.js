document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let tablaMiembros = null;

    const modalNuevoMiembroEl = document.getElementById('modalNuevoMiembro');
    const modalEditarMiembroEl = document.getElementById('modalEditarMiembro');
    const modalEliminarMiembroEl = document.getElementById('modalEliminarMiembro');
    const modalNuevoMiembro = modalNuevoMiembroEl ? new bootstrap.Modal(modalNuevoMiembroEl) : null;
    const modalEditarMiembro = modalEditarMiembroEl ? new bootstrap.Modal(modalEditarMiembroEl) : null;
    const modalEliminarMiembro = modalEliminarMiembroEl ? new bootstrap.Modal(modalEliminarMiembroEl) : null;

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
        window.showToast('error', error.message);
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

    function nombreCompleto(miembro) {
        return [
            miembro.nombre,
            miembro.apellido_paterno,
            miembro.apellido_materno
        ]
            .filter(Boolean)
            .join(' ');
    }

    function crearDireccionTexto(miembro) {
        const direccion = miembro.direccion;

        if (!direccion) {
            return '<span class="text-secondary">Sin dirección</span>';
        }
        let texto = escapeHtml(direccion.calle) + ' ' + escapeHtml(direccion.numero_exterior);

        if (direccion.numero_interior) {
            texto += ' Int. ' + escapeHtml(direccion.numero_interior);
        }
        texto += ' <span class="text-secondary">— ' + escapeHtml(direccion.colonia) + ', ' + escapeHtml(direccion.codigo_postal) + '</span>';
        return texto;
    }

    function crearAccionesMiembro(miembro, urls) {
        let html = '<div class="miembro-actions">';
        const acciones = window.accionesMiembros || [];

        acciones.forEach(function (accion) {

            if (accion.slug === 'miembros.editar') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-primary miembro-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEditarMiembro" ' +
                    'data-id="' + escapeAttribute(miembro.id) + '" ' +
                    'data-nombre="' + escapeAttribute(miembro.nombre) + '" ' +
                    'data-apellido-paterno="' + escapeAttribute(miembro.apellido_paterno) + '" ' +
                    'data-apellido-materno="' + escapeAttribute(miembro.apellido_materno) + '" ' +
                    'data-telefono="' + escapeAttribute(miembro.telefono) + '" ' +
                    'data-email="' + escapeAttribute(miembro.email) + '" ' +
                    'data-direccion-id="' + escapeAttribute(miembro.direccion_id) + '" ' +
                    'data-url="' + escapeAttribute(urls.update) + '">' +
                    (accion.icono || '<i class="fa-solid fa-pen"></i>') +
                    '</button>';
            }

            else if (accion.slug === 'miembros.usuario') {
                if (miembro.usuario_id) {
                    html +=
                        '<button type="button" ' +
                        'class="btn btn-sm btn-success miembro-action-btn miembro-usuario-btn" ' +
                        'title="Ya tiene usuario" ' +
                        'data-id="' + escapeAttribute(miembro.id) + '" ' +
                        'data-tiene-usuario="1" disabled>' +
                        '<i class="fa-solid fa-user-check"></i>' +
                        '</button>';
                } else {
                    html +=
                        '<button type="button" ' +
                        'class="btn btn-sm btn-outline-success miembro-action-btn miembro-usuario-btn" ' +
                        'title="' + escapeAttribute(accion.nombre) + '" ' +
                        'data-bs-toggle="modal" ' +
                        'data-bs-target="#modalUsuarioMiembro" ' +
                        'data-id="' + escapeAttribute(miembro.id) + '" ' +
                        'data-name="' + escapeAttribute(nombreCompleto(miembro)) + '" ' +
                        'data-url="' + escapeAttribute(urls.usuario || ('/miembros/' + miembro.id + '/usuario')) + '">' +
                        '<i class="fa-solid fa-user-lock"></i>' +
                        '</button>';
                }
            }
            else if (accion.slug === 'miembros.eliminar') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger miembro-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEliminarMiembro" ' +
                    'data-id="' + escapeAttribute(miembro.id) + '" ' +
                    'data-name="' + escapeAttribute(nombreCompleto(miembro)) + '" ' +
                    'data-url="' + escapeAttribute(urls.delete) + '">' +
                    (accion.icono || '<i class="fa-solid fa-trash"></i>') +
                    '</button>';
            }
        });
        html += '</div>';
        return html;
    }

    function crearFilaMiembro(miembro, urls = null) {
        const fila = document.createElement('tr');
        const urlsMiembro = urls || { update: `/miembros/${miembro.id}`, delete: `/miembros/${miembro.id}` };

        fila.innerHTML = `
        <td>${escapeHtml(nombreCompleto(miembro))}</td>
        <td>${escapeHtml(miembro.telefono || '—')}</td>
        <td>${escapeHtml(miembro.email || '—')}</td>
        <td>${crearDireccionTexto(miembro)}</td>
        <td class="text-end px-4">${crearAccionesMiembro(miembro, urlsMiembro)}</td>
        `;
        return fila;
    }

    if (document.querySelector('#tablaMiembros')) {
        tablaMiembros = new DataTable(
            '#tablaMiembros',
            {
                autoWidth: false,

                language: {
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    zeroRecords: 'No se encontraron miembros',
                    emptyTable: 'No hay miembros registrados',

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
                        targets: 4,
                        width: '15%'
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
        const paginacion = document.querySelector('#tablaMiembros_wrapper .dt-paging');

        if (!paginacion) {
            return;
        }
        const titulos = {
            'fa-angles-left': 'Primera página',
            'fa-angle-left': 'Página anterior',
            'fa-angle-right': 'Página siguiente',
            'fa-angles-right': 'Última página'
        };

        paginacion.querySelectorAll('button').forEach(function (button) {
            const icono = button.querySelector('i');

            if (!icono) {
                return;
            }

            Object.keys(titulos).forEach(function (clase) {
                if (icono.classList.contains(clase)) {
                    button.setAttribute('title', titulos[clase]);
                    button.setAttribute('aria-label', titulos[clase]);
                }
            });
        });
    }

    function ajustarFila(fila) {
        if (!fila) {
            return;
        }
        const celdas = fila.children;

        if (celdas.length < 5) {
            return;
        }

        celdas[0].style.width = '20%';
        celdas[1].style.width = '13%';
        celdas[2].style.width = '22%';
        celdas[3].style.width = '30%';
        celdas[4].style.width = '15%';

        celdas[4].classList.add(
            'text-end',
            'px-4'
        );
    }

    function ajustarTodasLasFilas() {
        if (!tablaMiembros) {
            return;
        }

        tablaMiembros
            .rows()
            .every(function () {
                ajustarFila(
                    this.node()
                );
            });
    }

    function agregarMiembroATabla(miembro, urls) {
        if (!tablaMiembros) {
            return;
        }
        const fila = crearFilaMiembro(miembro, urls);
        tablaMiembros.row.add(fila).draw(false);
        ajustarTodasLasFilas();
        tablaMiembros.columns.adjust();
    }

    function actualizarMiembroEnTabla(miembro, urls) {
        if (!tablaMiembros) {
            return;
        }

        tablaMiembros.rows()
            .every(function () {
                const fila = this.node();

                if (!fila) {
                    return;
                }
                const boton = fila.querySelector('.miembro-action-btn');

                if (boton && boton.dataset.id === String(miembro.id)) {
                    const datos = this.data();

                    datos[0] = escapeHtml(nombreCompleto(miembro));
                    datos[1] = escapeHtml(miembro.telefono || '—');
                    datos[2] = escapeHtml(miembro.email || '—');
                    datos[3] = crearDireccionTexto(miembro);
                    datos[4] = crearAccionesMiembro(miembro, urls);

                    this.data(datos);
                }
            });

        tablaMiembros.draw(false);
        ajustarTodasLasFilas();
        tablaMiembros.columns.adjust();
    }

    function eliminarMiembroDeTabla(id) {
        if (!tablaMiembros) {
            return;
        }

        tablaMiembros
            .rows()
            .every(function () {
                const fila = this.node();

                if (!fila) {
                    return;
                }
                const boton = fila.querySelector('.miembro-action-btn');

                if (boton && boton.dataset.id === String(id)) {
                    this.remove();
                }
            });

        tablaMiembros.draw(false);
        ajustarTodasLasFilas();
    }

    let datosOriginalesEditar = null;
    const MENSAJE_SIN_CAMBIOS = 'No hubo cambios para actualizar.';
    const modalEditar = document.getElementById('modalEditarMiembro');

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
                    nombre: (button.dataset.nombre || '').trim(),
                    apellido_paterno: (button.dataset.apellidoPaterno || '').trim(),
                    apellido_materno: (button.dataset.apellidoMaterno || '').trim(),
                    telefono: (button.dataset.telefono || '').trim(),
                    email: (button.dataset.email || '').trim(),
                    direccion_id: (button.dataset.direccionId || '').trim(),
                };

                document.getElementById('editar_id').value = button.dataset.id;
                document.getElementById('editar_nombre').value = button.dataset.nombre || '';
                document.getElementById('editar_apellido_paterno').value = button.dataset.apellidoPaterno || '';
                document.getElementById('editar_apellido_materno').value = button.dataset.apellidoMaterno || '';
                document.getElementById('editar_telefono').value = button.dataset.telefono || '';
                document.getElementById('editar_email').value = button.dataset.email || '';
                document.getElementById('editar_direccion_id').value = button.dataset.direccionId || '';
                document.getElementById('formEditarMiembro').setAttribute('action',button.dataset.url);
            }
        );
    }
    const modalEliminar = document.getElementById('modalEliminarMiembro');

    if (modalEliminar) {
        modalEliminar.addEventListener(
            'show.bs.modal',
            function (event) {

                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                document.getElementById('eliminar_id').value = button.dataset.id;
                document.getElementById('eliminar_nombre').textContent = button.dataset.name;
                document.getElementById('formEliminarMiembro').setAttribute('action',button.dataset.url);
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

        return peticion(form.action, 'POST', formData)
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

    const formNuevoMiembro = document.getElementById('formNuevoMiembro');

    if (formNuevoMiembro) {
        formNuevoMiembro.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                enviarFormulario(formNuevoMiembro, modalNuevoMiembro, 'Miembro creado correctamente.')
                    .then((data) => {
                        agregarMiembroATabla(
                            data.miembro,
                            data.urls
                        );
                        formNuevoMiembro.reset();
                    })
                    .catch(mostrarError);
            }
        );
    }
    const formEditarMiembro = document.getElementById('formEditarMiembro');

    if (formEditarMiembro) {
        formEditarMiembro.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const campos =
                    [
                        'nombre',
                        'apellido_paterno',
                        'apellido_materno',
                        'telefono',
                        'email',
                        'direccion_id'
                    ];

                const valoresActuales = {};

                campos.forEach(function (campo) {

                    let input;

                    if (campo === 'direccion_id') {
                        input = document.getElementById('editar_direccion_id');
                    } else {
                        input = document.getElementById('editar_' + campo);
                    }

                    valoresActuales[campo] = input ? input.value.trim() : '';
                });

                const sinCambios = datosOriginalesEditar && campos.every(function (campo) {
                        return (
                            valoresActuales[campo] === datosOriginalesEditar[campo]
                        );
                    });

                if (sinCambios) {
                    window.showToast('info', MENSAJE_SIN_CAMBIOS);
                    return;
                }

                enviarFormulario(formEditarMiembro, modalEditarMiembro, 'Miembro actualizado correctamente.')
                    .then((data) => {
                        actualizarMiembroEnTabla(
                            data.miembro,
                            {
                                update: `/miembros/${data.miembro.id}`,
                                delete: `/miembros/${data.miembro.id}`,
                            }
                        );
                    })
                    .catch(mostrarError);
            }
        );
    }

    const formEliminarMiembro = document.getElementById('formEliminarMiembro');

    if (formEliminarMiembro) {
        formEliminarMiembro.addEventListener('submit',
            function (event) {

                event.preventDefault();
                const id = document.getElementById('eliminar_id').value;

                enviarFormulario(formEliminarMiembro, modalEliminarMiembro, 'Miembro eliminado correctamente.')
                    .then((data) => {
                        eliminarMiembroDeTabla(data.id || id);
                    })
                    .catch(mostrarError);
            }
        );
    }
    const modalUsuarioMiembroEl = document.getElementById('modalUsuarioMiembro');
    const formUsuarioMiembro = document.getElementById('formUsuarioMiembro');

    if (modalUsuarioMiembroEl && formUsuarioMiembro) {
        const modalUsuarioMiembro = new bootstrap.Modal(modalUsuarioMiembroEl);
        const bloqueCrear = document.getElementById('usuario_bloque_crear');
        const bloqueVincular = document.getElementById('usuario_bloque_vincular');
        const politica = window.politicaPassword || {};
        const passwordMinimo = parseInt(politica.min, 10) || 8;
        const passwordComplejo = !!politica.complex;
        let botonOrigen = null;

        function modoActual() {
            const marcado = formUsuarioMiembro.querySelector('input[name="modo"]:checked');

            if (marcado) {
                return marcado.value;
            }

            const oculto = formUsuarioMiembro.querySelector('input[name="modo"][type="hidden"]');
            return oculto ? oculto.value : 'crear';
        }

        function aplicarModo() {
            const vincular = modoActual() === 'vincular';
            bloqueCrear.classList.toggle('d-none', vincular);

            if (bloqueVincular) {
                bloqueVincular.classList.toggle('d-none', !vincular);
            }
        }

        function marcarError(input, texto) {
            if (!input) {
                return;
            }
            input.classList.toggle('is-invalid', !!texto);
            const error = input.parentElement.querySelector('.invalid-feedback');

            if (error) {
                error.textContent = texto || '';
            }
        }

        function limpiarFormularioUsuario() {
            formUsuarioMiembro.reset();
            formUsuarioMiembro
                .querySelectorAll('.is-invalid')
                .forEach(function (input) {
                    marcarError(input, '');
                });

            aplicarModo();
        }

        formUsuarioMiembro
            .querySelectorAll('input[name="modo"]')
            .forEach(function (radio) {
                radio.addEventListener('change', aplicarModo);
            });

        modalUsuarioMiembroEl.addEventListener('show.bs.modal', function (event) {
            botonOrigen = event.relatedTarget;
            limpiarFormularioUsuario();

            if (!botonOrigen) {
                return;
            }
            document.getElementById('usuario_miembro_nombre').textContent = botonOrigen.dataset.name || 'este miembro';
            formUsuarioMiembro.setAttribute('action', botonOrigen.dataset.url);
        });

        function validarUsuarioMiembro() {
            const modo = modoActual();
            let valido = true;

            if (modo === 'vincular') {
                const select = document.getElementById('usuario_miembro_existente');

                if (!select || !select.value) {
                    marcarError(select, 'Selecciona un usuario.');
                    return false;
                }

                marcarError(select, '');
                return true;
            }

            const username = document.getElementById('usuario_miembro_username');
            const password = document.getElementById('usuario_miembro_password');
            const confirmacion = document.getElementById('usuario_miembro_password_confirmation');

            const valorUsuario = username.value.trim();

            if (valorUsuario.length < 3) {
                marcarError(username, 'El usuario debe tener al menos 3 caracteres.');
                valido = false;
            } else if (!/^[A-Za-z0-9._-]+$/.test(valorUsuario)) {
                marcarError(username, 'Solo letras, números, punto, guion y guion bajo (sin espacios).');
                valido = false;
            } else {
                marcarError(username, '');
            }

            let mensajeClave = '';

            if (password.value.length < passwordMinimo) {
                mensajeClave = 'La contraseña debe tener al menos ' + passwordMinimo + ' caracteres.';
            } else if ( passwordComplejo && !(/\p{Ll}/u.test(password.value) && /\p{Lu}/u.test(password.value) && /\d/.test(password.value))) {
                mensajeClave = 'La contraseña debe incluir una mayúscula, una minúscula y un número.';
            }

            marcarError(password, mensajeClave);

            if (mensajeClave) {
                valido = false;
            }

            if (confirmacion.value !== password.value) {
                marcarError(confirmacion, 'Las contraseñas no coinciden.');
                valido = false;
            } else {
                marcarError(confirmacion, '');
            }
            return valido;
        }

        formUsuarioMiembro.addEventListener('submit', function (event) {
            event.preventDefault();

            if (!validarUsuarioMiembro()) {
                return;
            }

            enviarFormulario(
                formUsuarioMiembro,
                modalUsuarioMiembro,
                'Usuario asignado correctamente.'
            )
                .then(function () {
                    if (!botonOrigen) {
                        return;
                    }

                    botonOrigen.className = 'btn btn-sm btn-success miembro-action-btn miembro-usuario-btn';
                    botonOrigen.disabled = true;
                    botonOrigen.title = 'Ya tiene usuario';
                    botonOrigen.dataset.tieneUsuario = '1';
                    botonOrigen.removeAttribute('data-bs-toggle');
                    botonOrigen.removeAttribute('data-bs-target');
                    botonOrigen.innerHTML = '<i class="fa-solid fa-user-check"></i>';
                })
                .catch(mostrarError);
        });
    }
    ajustarTodasLasFilas();
});
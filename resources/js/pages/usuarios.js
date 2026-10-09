document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let tablaUsuarios = null;

    const modalNuevoUsuarioEl = document.getElementById('modalNuevoUsuario');
    const modalEditarUsuarioEl = document.getElementById('modalEditarUsuario');
    const modalPasswordUsuarioEl = document.getElementById('modalPasswordUsuario');
    const modalRolesUsuarioEl = document.getElementById('modalRolesUsuario');
    const modalEliminarUsuarioEl = document.getElementById('modalEliminarUsuario');

    const modalNuevoUsuario = modalNuevoUsuarioEl
        ? new bootstrap.Modal(modalNuevoUsuarioEl)
        : null;

    const modalEditarUsuario = modalEditarUsuarioEl
        ? new bootstrap.Modal(modalEditarUsuarioEl)
        : null;

    const modalPasswordUsuario = modalPasswordUsuarioEl
        ? new bootstrap.Modal(modalPasswordUsuarioEl)
        : null;

    const modalRolesUsuario = modalRolesUsuarioEl
        ? new bootstrap.Modal(modalRolesUsuarioEl)
        : null;

    const modalEliminarUsuario = modalEliminarUsuarioEl
        ? new bootstrap.Modal(modalEliminarUsuarioEl)
        : null;

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
                    const primerError =
                        Object.values(data.errors).flat()[0];

                    const error = new Error(
                        primerError ||
                        data.mensaje ||
                        data.message ||
                        'Ocurrió un error al procesar la solicitud.'
                    );

                    error.errors = data.errors;

                    throw error;
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
            error.message
        );
    }

    // Marca cada campo con el error que devolvió el servidor; si no hay
    // campo para el error, lo muestra como aviso.
    function mostrarErroresForm(form, error) {
        if (!error || !error.errors || !form) {
            mostrarError(error);
            return;
        }

        let primero = null;

        Object.keys(error.errors).forEach(function (campo) {
            const nombre = campo.split('.')[0];

            const input = form.querySelector('[name="' + nombre + '"]');

            if (!input || input.type === 'checkbox') {
                return;
            }

            input.classList.add('is-invalid');

            const aviso = input.parentElement.querySelector('.invalid-feedback');

            if (aviso) {
                aviso.textContent = error.errors[campo][0];
            }

            primero = primero || input;
        });

        if (primero) {
            primero.focus();
        } else {
            mostrarError(error);
        }
    }

    function limpiarErroresForm(form) {
        if (!form) {
            return;
        }

        form.querySelectorAll('.is-invalid').forEach(function (input) {
            input.classList.remove('is-invalid');
        });

        form.querySelectorAll('.invalid-feedback').forEach(function (aviso) {
            if (!aviso.id || !/password/.test(aviso.id)) {
                aviso.textContent = '';
            }
        });
    }

    function confirmarAccion(opciones) {
        if (!window.Swal) {
            return Promise.resolve(window.confirm(opciones.titulo));
        }

        const cuerpo = getComputedStyle(document.body);
        const referencia = document.querySelector('.modal-content');

        let fondo = referencia
            ? getComputedStyle(referencia).backgroundColor
            : cuerpo.backgroundColor;

        if (!fondo || fondo === 'transparent' || fondo === 'rgba(0, 0, 0, 0)') {
            fondo = cuerpo.backgroundColor;
        }

        return Swal.fire({
            background: fondo,
            color: cuerpo.color,
            icon: 'warning',
            title: opciones.titulo,
            text: opciones.texto,
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: opciones.textoConfirmar,
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
            return resultado.isConfirmed;
        });
    }

    // Política de contraseña de Configuración > Seguridad (la imprime index.blade.php)
    const politicaPassword = window.politicaPassword || {};
    const passwordMinimo = parseInt(politicaPassword.min, 10) || 8;
    const passwordComplejo = !!politicaPassword.complex;

    function mensajePassword(valor) {
        if (valor.length < passwordMinimo) {
            return 'La contraseña debe tener al menos ' +
                passwordMinimo + ' caracteres.';
        }

        if (
            passwordComplejo &&
            !(
                /\p{Ll}/u.test(valor) &&
                /\p{Lu}/u.test(valor) &&
                /\d/.test(valor)
            )
        ) {
            return 'La contraseña debe incluir al menos una mayúscula, una minúscula y un número.';
        }

        return '';
    }

    function errorCampo(input, errorDiv, texto) {
        if (!input) {
            return;
        }

        input.classList.remove('is-valid');
        input.classList.toggle('is-invalid', !!texto);

        if (errorDiv) {
            errorDiv.textContent = texto || '';
        }
    }

    function limpiarCampo(input, errorDiv) {
        if (input) {
            input.classList.remove('is-invalid', 'is-valid');
        }

        if (errorDiv) {
            errorDiv.textContent = '';
        }
    }

    // Enlaza un par contraseña / confirmación con la política
    function enlazarPassword(idPassword, idConfirmacion, idErrorPassword, idErrorConfirmacion) {
        const password = document.getElementById(idPassword);
        const confirmacion = document.getElementById(idConfirmacion);
        const errorPassword = document.getElementById(idErrorPassword);
        const errorConfirmacion = document.getElementById(idErrorConfirmacion);

        if (!password) {
            return null;
        }

        function validarConfirmacion() {
            if (!confirmacion || confirmacion.value === '') {
                limpiarCampo(confirmacion, errorConfirmacion);
                return;
            }

            if (confirmacion.value === password.value) {
                limpiarCampo(confirmacion, errorConfirmacion);
                confirmacion.classList.add('is-valid');
            } else {
                errorCampo(
                    confirmacion,
                    errorConfirmacion,
                    'Las contraseñas no coinciden.'
                );
            }
        }

        function validarPassword(alSalir) {
            if (password.value === '') {
                limpiarCampo(password, errorPassword);
                return;
            }

            const mensaje = mensajePassword(password.value);

            if (!mensaje) {
                limpiarCampo(password, errorPassword);
                password.classList.add('is-valid');
            } else if (alSalir || password.classList.contains('is-invalid')) {
                errorCampo(password, errorPassword, mensaje);
            } else {
                password.classList.remove('is-valid');
            }
        }

        password.addEventListener('input', function () {
            validarPassword(false);
            validarConfirmacion();
        });

        password.addEventListener('blur', function () {
            validarPassword(true);
        });

        if (confirmacion) {
            confirmacion.addEventListener('input', validarConfirmacion);
        }

        return {
            // true si todo está bien; si no, marca el error y enfoca el campo
            validar: function () {
                const mensaje = password.value === ''
                    ? 'La contraseña es obligatoria.'
                    : mensajePassword(password.value);

                if (mensaje) {
                    errorCampo(password, errorPassword, mensaje);
                    password.focus();

                    return false;
                }

                if (confirmacion && confirmacion.value !== password.value) {
                    errorCampo(
                        confirmacion,
                        errorConfirmacion,
                        'Las contraseñas no coinciden.'
                    );
                    confirmacion.focus();

                    return false;
                }

                return true;
            },

            limpiar: function () {
                limpiarCampo(password, errorPassword);
                limpiarCampo(confirmacion, errorConfirmacion);
            },
        };
    }

    const politicaNuevo = enlazarPassword(
        'password',
        'password_confirmation',
        'password-error',
        'password-confirmation-error'
    );

    const politicaCambio = enlazarPassword(
        'password_nueva',
        'password_nueva_confirmation',
        'password-nueva-error',
        'password-nueva-confirmation-error'
    );

    if (modalNuevoUsuarioEl && politicaNuevo) {
        modalNuevoUsuarioEl.addEventListener(
            'hidden.bs.modal',
            politicaNuevo.limpiar
        );
    }

    if (modalPasswordUsuarioEl && politicaCambio) {
        modalPasswordUsuarioEl.addEventListener(
            'show.bs.modal',
            politicaCambio.limpiar
        );
    }

    function escapeHtml(valor) {
        const div =
            document.createElement('div');

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    function escapeAttribute(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function crearAccionesUsuario(usuario, urls) {
        let html =
            '<div class="usuario-actions">';

        const acciones =
            window.accionesUsuarios || [];

        acciones.forEach(function (accion) {

            if (accion.slug === 'usuarios.editar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-primary usuario-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEditarUsuario" ' +
                    'data-id="' + escapeAttribute(usuario.id) + '" ' +
                    'data-name="' + escapeAttribute(usuario.name) + '" ' +
                    'data-username="' + escapeAttribute(usuario.username) + '" ' +
                    'data-nombre="' + escapeAttribute(usuario.nombre) + '" ' +
                    'data-apellido-paterno="' + escapeAttribute(usuario.apellido_paterno) + '" ' +
                    'data-apellido-materno="' + escapeAttribute(usuario.apellido_materno) + '" ' +
                    'data-telefono="' + escapeAttribute(usuario.telefono) + '" ' +
                    'data-email="' + escapeAttribute(usuario.email) + '" ' +
                    'data-activo="' + (usuario.activo ? 1 : 0) + '" ' +
                    'data-url="' + escapeAttribute(urls.update) + '">' +
                    (accion.icono || '<i class="fa-solid fa-pen"></i>') +
                    '</button>';

            }

            else if (accion.slug === 'usuarios.toggle') {

                html +=
                    '<button type="button" ' +
                    'class="usuario-toggle-btn btn-estado-usuario ' +
                    (usuario.activo ? 'activo' : 'inactivo') + '" ' +
                    'title="' + (usuario.activo ? 'Desactivar usuario' : 'Activar usuario') + '" ' +
                    'data-tooltip="' + (usuario.activo ? 'Desactivar usuario' : 'Activar usuario') + '" ' +
                    'aria-pressed="' + (usuario.activo ? 'true' : 'false') + '" ' +
                    'data-id="' + escapeAttribute(usuario.id) + '" ' +
                    'data-name="' + escapeAttribute(usuario.username) + '" ' +
                    'data-activo="' + (usuario.activo ? 1 : 0) + '" ' +
                    'data-url="' + escapeAttribute(urls.estado) + '">' +
                    '<span class="usuario-toggle-track">' +
                    '<span class="usuario-toggle-thumb"></span>' +
                    '</span>' +
                    '</button>';
            }

            else if (accion.slug === 'usuarios.password') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-warning usuario-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalPasswordUsuario" ' +
                    'data-id="' + escapeAttribute(usuario.id) + '" ' +
                    'data-name="' + escapeAttribute(usuario.username) + '" ' +
                    'data-url="' + escapeAttribute(urls.password) + '">' +
                    (accion.icono || '<i class="fa-solid fa-key"></i>') +
                    '</button>';
            }

            else if (accion.slug === 'usuarios.roles') {

                const roles =
                    usuario.roles
                        ? usuario.roles
                            .map((rol) => rol.id)
                            .join(',')
                        : '';

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-success usuario-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalRolesUsuario" ' +
                    'data-id="' + escapeAttribute(usuario.id) + '" ' +
                    'data-name="' + escapeAttribute(usuario.username) + '" ' +
                    'data-roles="' + escapeAttribute(roles) + '" ' +
                    'data-url="' + escapeAttribute(urls.roles) + '">' +
                    (accion.icono || '<i class="fa-solid fa-user-shield"></i>') +
                    '</button>';
            }

            else if (accion.slug === 'usuarios.eliminar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger usuario-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEliminarUsuario" ' +
                    'data-id="' + escapeAttribute(usuario.id) + '" ' +
                    'data-name="' + escapeAttribute(usuario.username) + '" ' +
                    'data-url="' + escapeAttribute(urls.delete) + '">' +
                    (accion.icono || '<i class="fa-solid fa-trash"></i>') +
                    '</button>';
            }

        });

        html +=
            '</div>';

        return html;
    }

    function formatearFecha(valor) {
        const fecha =
            valor
                ? new Date(valor)
                : null;

        if (!fecha || isNaN(fecha.getTime())) {
            return '';
        }

        const dos = (n) => String(n).padStart(2, '0');

        return dos(fecha.getDate()) + '/' +
            dos(fecha.getMonth() + 1) + '/' +
            fecha.getFullYear() + ' ' +
            dos(fecha.getHours()) + ':' +
            dos(fecha.getMinutes());
    }

    function nombreCompletoUsuario(usuario) {
        return [
            usuario.nombre,
            usuario.apellido_paterno,
            usuario.apellido_materno
        ].filter(Boolean).join(' ').trim() || '—';
    }

    // Las 6 columnas de la tabla, en HTML
    function celdasUsuario(usuario, urls) {
        const urlsUsuario =
            urls || {
                update: `/usuarios/${usuario.id}`,
                estado: `/usuarios/${usuario.id}/estado`,
                password: `/usuarios/${usuario.id}/password`,
                roles: `/usuarios/${usuario.id}/roles`,
                delete: `/usuarios/${usuario.id}`,
            };

        return [
            '<span class="fw-semibold">' + escapeHtml(usuario.username) + '</span>',
            escapeHtml(nombreCompletoUsuario(usuario)),
            escapeHtml(usuario.email || '—'),
            usuario.activo
                ? '<span class="badge rounded-pill text-bg-success">Activo</span>'
                : '<span class="badge rounded-pill text-bg-secondary">Inactivo</span>',
            '<span class="text-secondary">' + formatearFecha(usuario.created_at) + '</span>',
            crearAccionesUsuario(usuario, urlsUsuario),
        ];
    }

    if (document.querySelector('#tablaUsuarios')) {
        tablaUsuarios = new DataTable(
            '#tablaUsuarios',
            {
                autoWidth: false,

                language: {
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_ registros',
                    info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                    infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                    infoFiltered: '(filtrado de _MAX_ registros)',
                    zeroRecords: 'No se encontraron usuarios',
                    emptyTable: 'No hay usuarios registrados',

                    paginate: {
                        first: 'Primero',
                        previous: 'Anterior',
                        next: 'Siguiente',
                        last: 'Último'
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
                        width: '18%',
                        className: 'text-end px-4'
                    }
                ],

                layout: {
                    topStart: 'pageLength',
                    topEnd: 'search',
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                }
            }
        );
    }

    function agregarUsuarioATabla(usuario, urls) {
        if (!tablaUsuarios) {
            return;
        }

        tablaUsuarios.row
            .add(celdasUsuario(usuario, urls))
            .draw(false);

        tablaUsuarios.columns.adjust();
    }

    function actualizarUsuarioEnTabla(usuario, urls) {
        if (!tablaUsuarios) {
            return;
        }

        tablaUsuarios
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (!fila) {
                    return;
                }

                const boton =
                    fila.querySelector(
                        '.usuario-action-btn'
                    );

                if (
                    boton &&
                    boton.dataset.id ===
                    String(usuario.id)
                ) {
                    this.data(
                        celdasUsuario(usuario, urls)
                    );
                }
            });

        tablaUsuarios.draw(false);
        tablaUsuarios.columns.adjust();
    }

    function actualizarRolesEnTabla(usuario) {
        if (!tablaUsuarios || !usuario) {
            return;
        }

        tablaUsuarios
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (!fila) {
                    return;
                }

                const boton =
                    fila.querySelector(
                        '.usuario-action-btn'
                    );

                if (
                    boton &&
                    boton.dataset.id ===
                    String(usuario.id)
                ) {
                    const roles =
                        usuario.roles
                            ? usuario.roles
                                .map((rol) => rol.id)
                                .join(',')
                            : '';

                    const botonRoles =
                        fila.querySelector(
                            '[data-bs-target="#modalRolesUsuario"]'
                        );

                    if (botonRoles) {
                        botonRoles.dataset.roles =
                            roles;
                    }
                }
            });
    }

    function eliminarUsuarioDeTabla(id) {
        if (!tablaUsuarios) {
            return;
        }

        tablaUsuarios
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (!fila) {
                    return;
                }

                const boton =
                    fila.querySelector(
                        '.usuario-action-btn'
                    );

                if (
                    boton &&
                    boton.dataset.id ===
                    String(id)
                ) {
                    this.remove();
                }
            });

        tablaUsuarios.draw(false);
    }

    // Valores con los que se abrió cada modal, para detectar si hubo cambios
    let datosOriginalesEditar = null;
    let rolesOriginales = null;

    const MENSAJE_SIN_CAMBIOS = 'No hubo cambios para actualizar.';

    const modalEditar =
        document.getElementById(
            'modalEditarUsuario'
        );

    if (modalEditar) {
        modalEditar.addEventListener(
            'show.bs.modal',
            function (event) {
                const button =
                    event.relatedTarget;

                datosOriginalesEditar = null;

                if (!button) {
                    return;
                }

                const d = button.dataset;

                datosOriginalesEditar = {
                    username: (d.username || '').trim(),
                    nombre: (d.nombre || '').trim(),
                    apellido_paterno: (d.apellidoPaterno || '').trim(),
                    apellido_materno: (d.apellidoMaterno || '').trim(),
                    telefono: (d.telefono || '').trim(),
                    email: (d.email || '').trim(),
                    activo: d.activo === '1',
                };

                document.getElementById('editar_id').value = d.id;
                document.getElementById('editar_username').value = d.username || '';
                document.getElementById('editar_nombre').value = d.nombre || '';
                document.getElementById('editar_apellido_paterno').value = d.apellidoPaterno || '';
                document.getElementById('editar_apellido_materno').value = d.apellidoMaterno || '';
                document.getElementById('editar_telefono').value = d.telefono || '';
                document.getElementById('editar_email').value = d.email || '';

                limpiarErroresForm(
                    document.getElementById('formEditarUsuario')
                );

                document.getElementById(
                    'formEditarUsuario'
                ).setAttribute(
                    'action',
                    button.dataset.url
                );
            }
        );
    }

    const modalPassword =
        document.getElementById(
            'modalPasswordUsuario'
        );

    if (modalPassword) {
        modalPassword.addEventListener(
            'show.bs.modal',
            function (event) {
                const button =
                    event.relatedTarget;

                if (!button) {
                    return;
                }

                document.getElementById(
                    'password_usuario_id'
                ).value =
                    button.dataset.id;

                document.getElementById(
                    'password_usuario_nombre'
                ).textContent =
                    button.dataset.name;

                document.getElementById(
                    'password_nueva'
                ).value = '';

                document.getElementById(
                    'password_nueva_confirmation'
                ).value = '';

                document.getElementById(
                    'formPasswordUsuario'
                ).setAttribute(
                    'action',
                    button.dataset.url
                );
            }
        );
    }

    const modalRoles =
        document.getElementById(
            'modalRolesUsuario'
        );

    if (modalRoles) {
        modalRoles.addEventListener(
            'show.bs.modal',
            function (event) {
                const button =
                    event.relatedTarget;

                rolesOriginales = null;

                if (!button) {
                    return;
                }

                const roles =
                    button.dataset.roles
                        ? button.dataset.roles.split(',')
                        : [];

                rolesOriginales =
                    roles
                        .filter(Boolean)
                        .sort()
                        .join(',');

                document.getElementById(
                    'roles_usuario_id'
                ).value =
                    button.dataset.id;

                document.getElementById(
                    'roles_usuario_nombre'
                ).textContent =
                    button.dataset.name;

                document
                    .querySelectorAll(
                        '.rol-usuario-checkbox'
                    )
                    .forEach(function (checkbox) {
                        checkbox.checked =
                            roles.includes(
                                checkbox.value
                            );
                    });

                document.getElementById(
                    'formRolesUsuario'
                ).setAttribute(
                    'action',
                    button.dataset.url
                );
            }
        );
    }

    const modalEliminar =
        document.getElementById(
            'modalEliminarUsuario'
        );

    if (modalEliminar) {
        modalEliminar.addEventListener(
            'show.bs.modal',
            function (event) {
                const button =
                    event.relatedTarget;

                if (!button) {
                    return;
                }

                document.getElementById(
                    'eliminar_id'
                ).value =
                    button.dataset.id;

                document.getElementById(
                    'eliminar_nombre'
                ).textContent =
                    button.dataset.name;

                document.getElementById(
                    'formEliminarUsuario'
                ).setAttribute(
                    'action',
                    button.dataset.url
                );
            }
        );
    }

    function enviarFormulario(
        form,
        modal,
        mensajePorDefecto
    ) {
        const botonSubmit =
            form.querySelector(
                'button[type="submit"]'
            );

        const textoOriginal =
            botonSubmit?.innerHTML;

        if (botonSubmit) {
            botonSubmit.disabled = true;

            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Guardando...';
        }

        const formData =
            new FormData(form);

        return peticion(
            form.action,
            'POST',
            formData
        )
            .then((data) => {
                cerrarModal(modal);

                window.showToast(
                    'success',
                    data.mensaje ||
                    mensajePorDefecto
                );

                return data;
            })
            .finally(() => {
                if (botonSubmit) {
                    botonSubmit.disabled = false;

                    botonSubmit.innerHTML =
                        textoOriginal;
                }
            });
    }

    const formNuevoUsuario =
        document.getElementById(
            'formNuevoUsuario'
        );

    if (formNuevoUsuario) {
        formNuevoUsuario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                limpiarErroresForm(formNuevoUsuario);

                if (politicaNuevo && !politicaNuevo.validar()) {
                    return;
                }

                enviarFormulario(
                    formNuevoUsuario,
                    modalNuevoUsuario,
                    'Usuario creado correctamente.'
                )
                    .then((data) => {
                        agregarUsuarioATabla(
                            data.usuario,
                            data.urls
                        );

                        formNuevoUsuario.reset();
                    })
                    .catch(function (error) {
                        mostrarErroresForm(formNuevoUsuario, error);
                    });
            }
        );
    }

    const formEditarUsuario =
        document.getElementById(
            'formEditarUsuario'
        );

    if (formEditarUsuario) {
        formEditarUsuario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const valor = (id) =>
                    document.getElementById(id).value.trim();

                const actuales = {
                    username: valor('editar_username'),
                    nombre: valor('editar_nombre'),
                    apellido_paterno: valor('editar_apellido_paterno'),
                    apellido_materno: valor('editar_apellido_materno'),
                    telefono: valor('editar_telefono'),
                    email: valor('editar_email'),
                };

                if (
                    datosOriginalesEditar &&
                    Object.keys(actuales).every(function (campo) {
                        return actuales[campo] === datosOriginalesEditar[campo];
                    })
                ) {
                    window.showToast(
                        'info',
                        MENSAJE_SIN_CAMBIOS
                    );

                    return;
                }

                limpiarErroresForm(formEditarUsuario);

                enviarFormulario(
                    formEditarUsuario,
                    modalEditarUsuario,
                    'Datos del usuario actualizados correctamente.'
                )
                    .then((data) => {
                        actualizarUsuarioEnTabla(
                            data.usuario,
                            data.urls
                        );
                    })
                    .catch(function (error) {
                        mostrarErroresForm(formEditarUsuario, error);
                    });
            }
        );
    }

    const formPasswordUsuario =
        document.getElementById(
            'formPasswordUsuario'
        );

    if (formPasswordUsuario) {
        formPasswordUsuario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                if (politicaCambio && !politicaCambio.validar()) {
                    return;
                }

                enviarFormulario(
                    formPasswordUsuario,
                    modalPasswordUsuario,
                    'Contraseña actualizada correctamente.'
                ).catch(mostrarError);
            }
        );
    }

    const formRolesUsuario =
        document.getElementById(
            'formRolesUsuario'
        );

    if (formRolesUsuario) {
        formRolesUsuario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const rolesSeleccionados =
                    Array.from(
                        formRolesUsuario.querySelectorAll(
                            '.rol-usuario-checkbox:checked'
                        )
                    )
                        .map(function (checkbox) {
                            return checkbox.value;
                        })
                        .sort()
                        .join(',');

                if (
                    rolesOriginales !== null &&
                    rolesSeleccionados === rolesOriginales
                ) {
                    window.showToast(
                        'info',
                        MENSAJE_SIN_CAMBIOS
                    );

                    return;
                }

                enviarFormulario(
                    formRolesUsuario,
                    modalRolesUsuario,
                    'Roles del usuario actualizados correctamente.'
                )
                    .then((data) => {
                        actualizarRolesEnTabla(
                            data.usuario
                        );
                    })
                    .catch(mostrarError);
            }
        );
    }

    const formEliminarUsuario =
        document.getElementById(
            'formEliminarUsuario'
        );

    if (formEliminarUsuario) {
        formEliminarUsuario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const id =
                    document.getElementById(
                        'formEliminarUsuario'
                    )
                        .querySelector(
                            'input[name="id"]'
                        )?.value;

                enviarFormulario(
                    formEliminarUsuario,
                    modalEliminarUsuario,
                    'Usuario eliminado correctamente.'
                )
                    .then((data) => {
                        eliminarUsuarioDeTabla(
                            data.id || id
                        );
                    })
                    .catch(mostrarError);
            }
        );
    }

    [formNuevoUsuario, formEditarUsuario].forEach(function (form) {
        if (!form) {
            return;
        }

        form.addEventListener('input', function (event) {
            const input = event.target;

            if (!input.classList.contains('is-invalid') || /password/.test(input.name)) {
                return;
            }

            input.classList.remove('is-invalid');

            const aviso = input.parentElement.querySelector('.invalid-feedback');

            if (aviso) {
                aviso.textContent = '';
            }
        });
    });

    if (modalNuevoUsuarioEl && formNuevoUsuario) {
        modalNuevoUsuarioEl.addEventListener('hidden.bs.modal', function () {
            limpiarErroresForm(formNuevoUsuario);
        });
    }

    // Activar / desactivar usuario
    if (tablaUsuarios) {
        document.querySelector('#tablaUsuarios').addEventListener('click', function (event) {
            const boton = event.target.closest('.btn-estado-usuario');

            if (!boton || boton.disabled) {
                return;
            }

            const activar = boton.dataset.activo !== '1';

            const ejecutar = function () {
                boton.disabled = true;

                const cuerpo = new FormData();
                cuerpo.append('_method', 'PUT');
                cuerpo.append('activo', activar ? '1' : '0');

                peticion(boton.dataset.url, 'POST', cuerpo)
                    .then(function (data) {
                        window.showToast('success', data.mensaje);

                        actualizarUsuarioEnTabla(data.usuario, data.urls);
                    })
                    .catch(function (error) {
                        mostrarError(error);
                        boton.disabled = false;
                    });
            };

            if (activar) {
                ejecutar();

                return;
            }

            confirmarAccion({
                titulo: '¿Desactivar usuario?',
                texto: 'El usuario "' + boton.dataset.name + '" ya no podrá iniciar sesión.',
                textoConfirmar: 'Desactivar',
            }).then(function (confirmado) {
                if (confirmado) {
                    ejecutar();
                }
            });
        });
    }
});
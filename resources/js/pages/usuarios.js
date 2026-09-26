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
            error.message
        );
    }

    function actualizarBotonUsuario(
        fila,
        usuario
    ) {
        if (!fila || !usuario) {
            return;
        }

        const botones =
            fila.querySelectorAll(
                '.usuario-action-btn'
            );

        botones.forEach((boton) => {
            boton.dataset.name =
                usuario.name;

            if (
                boton.classList.contains(
                    'btn-outline-primary'
                )
            ) {
                boton.dataset.email =
                    usuario.email;
            }
        });
    }

    function crearFilaUsuario(usuario) {
        const fila =
            document.createElement('tr');

        const fecha =
            usuario.created_at
                ? new Date(usuario.created_at)
                : null;

        let fechaTexto = '';

        if (fecha && !isNaN(fecha.getTime())) {
            const dia = String(
                fecha.getDate()
            ).padStart(2, '0');

            const mes = String(
                fecha.getMonth() + 1
            ).padStart(2, '0');

            const año =
                fecha.getFullYear();

            const horas = String(
                fecha.getHours()
            ).padStart(2, '0');

            const minutos = String(
                fecha.getMinutes()
            ).padStart(2, '0');

            fechaTexto =
                `${dia}/${mes}/${año} ${horas}:${minutos}`;
        }

        const roles =
            usuario.roles
                ? usuario.roles
                    .map((rol) => rol.id)
                    .join(',')
                : '';

        fila.innerHTML = `
            <td>
                ${escapeHtml(usuario.name)}
            </td>

            <td>
                ${escapeHtml(usuario.email)}
            </td>

            <td>
                <span class="text-secondary">
                    ${fechaTexto}
                </span>
            </td>

            <td class="text-end px-4">
                <div class="usuario-actions">

                    <button type="button"
                        class="btn btn-sm btn-outline-primary usuario-action-btn"
                        title="Editar datos"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEditarUsuario"
                        data-id="${usuario.id}"
                        data-name="${escapeAttribute(usuario.name)}"
                        data-email="${escapeAttribute(usuario.email)}"
                        data-url="/usuarios/${usuario.id}">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button type="button"
                        class="btn btn-sm btn-outline-warning usuario-action-btn"
                        title="Cambiar contraseña"
                        data-bs-toggle="modal"
                        data-bs-target="#modalPasswordUsuario"
                        data-id="${usuario.id}"
                        data-name="${escapeAttribute(usuario.name)}"
                        data-url="/usuarios/${usuario.id}/password">
                        <i class="fa-solid fa-key"></i>
                    </button>

                    <button type="button"
                        class="btn btn-sm btn-outline-success usuario-action-btn"
                        title="Asignar roles"
                        data-bs-toggle="modal"
                        data-bs-target="#modalRolesUsuario"
                        data-id="${usuario.id}"
                        data-name="${escapeAttribute(usuario.name)}"
                        data-roles="${roles}"
                        data-url="/usuarios/${usuario.id}/roles">
                        <i class="fa-solid fa-user-shield"></i>
                    </button>

                    <button type="button"
                        class="btn btn-sm btn-outline-danger usuario-action-btn"
                        title="Eliminar usuario"
                        data-bs-toggle="modal"
                        data-bs-target="#modalEliminarUsuario"
                        data-id="${usuario.id}"
                        data-name="${escapeAttribute(usuario.name)}"
                        data-url="/usuarios/${usuario.id}">
                        <i class="fa-solid fa-trash"></i>
                    </button>

                </div>
            </td>
        `;

        return fila;
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

    if (document.querySelector('#tablaUsuarios')) {
        tablaUsuarios = new DataTable(
            '#tablaUsuarios',
            {
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
                    [1, 'asc']
                ],

                columnDefs: [
                    {
                        orderable: false,
                        searchable: false,
                        targets: 3
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

    function agregarUsuarioATabla(usuario) {
        if (!tablaUsuarios) {
            return;
        }

        const fila =
            crearFilaUsuario(usuario);

        tablaUsuarios.row
            .add(fila)
            .draw(false);
    }

    function actualizarUsuarioEnTabla(usuario) {
        if (!tablaUsuarios) {
            return;
        }

        const filas =
            tablaUsuarios.rows().nodes();

        filas.each(function (fila) {
            const boton =
                fila.querySelector(
                    '.usuario-action-btn'
                );

            if (
                boton &&
                boton.dataset.id ===
                    String(usuario.id)
            ) {
                fila.cells[0].textContent =
                    usuario.name;

                fila.cells[1].textContent =
                    usuario.email;

                actualizarBotonUsuario(
                    fila,
                    usuario
                );
            }
        });

        tablaUsuarios.draw(false);
    }

    function actualizarRolesEnTabla(usuario) {
        if (!tablaUsuarios || !usuario) {
            return;
        }

        const filas =
            tablaUsuarios.rows().nodes();

        filas.each(function (fila) {
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
                        '.btn-outline-success'
                    );

                if (botonRoles) {
                    botonRoles.dataset.roles =
                        roles;

                    botonRoles.dataset.name =
                        usuario.name;
                }
            }
        });
    }

    function eliminarUsuarioDeTabla(id) {
        if (!tablaUsuarios) {
            return;
        }

        const filas =
            tablaUsuarios.rows().nodes();

        filas.each(function (fila) {
            const boton =
                fila.querySelector(
                    '.usuario-action-btn'
                );

            if (
                boton &&
                boton.dataset.id ===
                    String(id)
            ) {
                tablaUsuarios
                    .row(fila)
                    .remove();
            }
        });

        tablaUsuarios.draw(false);
    }

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

                if (!button) {
                    return;
                }

                document.getElementById(
                    'editar_id'
                ).value =
                    button.dataset.id;

                document.getElementById(
                    'editar_name'
                ).value =
                    button.dataset.name;

                document.getElementById(
                    'editar_email'
                ).value =
                    button.dataset.email;

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

                if (!button) {
                    return;
                }

                const roles =
                    button.dataset.roles
                        ? button.dataset.roles.split(',')
                        : [];

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

                enviarFormulario(
                    formNuevoUsuario,
                    modalNuevoUsuario,
                    'Usuario creado correctamente.'
                )
                    .then((data) => {
                        agregarUsuarioATabla(
                            data.usuario
                        );

                        formNuevoUsuario.reset();
                    })
                    .catch(mostrarError);
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

                enviarFormulario(
                    formEditarUsuario,
                    modalEditarUsuario,
                    'Datos del usuario actualizados correctamente.'
                )
                    .then((data) => {
                        actualizarUsuarioEnTabla(
                            data.usuario
                        );
                    })
                    .catch(mostrarError);
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
});
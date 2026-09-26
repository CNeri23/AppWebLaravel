document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

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

    const tablaRolesElement = document.querySelector('#tablaRoles');

    let tablaRoles = null;

    if (tablaRolesElement) {
        tablaRoles = new DataTable(tablaRolesElement, {
            autoWidth: false,

            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron roles',
                emptyTable: 'No hay roles registrados',

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
                    targets: 3,
                    width: '17%'
                }
            ],
            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging'
            }
        });
    }

    const modalNuevo = document.getElementById('modalNuevoRol');
    const modalEditar = document.getElementById('modalEditarRol');
    const modalEliminar = document.getElementById('modalEliminarRol');

    const modalNuevoRol =
        modalNuevo
            ? new bootstrap.Modal(modalNuevo)
            : null;

    const modalEditarRol =
        modalEditar
            ? new bootstrap.Modal(modalEditar)
            : null;

    const modalEliminarRol =
        modalEliminar
            ? new bootstrap.Modal(modalEliminar)
            : null;

    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const name = button.dataset.name;
            const description = button.dataset.description;
            const url = button.dataset.url;

            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            document.getElementById('editar_description').value =
                description || '';

            document.getElementById('formEditarRol').setAttribute(
                'action',
                url
            );
        });
    }

    if (modalEliminar) {
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const name = button.dataset.name;
            const url = button.dataset.url;

            document.getElementById('eliminar_id').value = id;
            document.getElementById('eliminar_nombre').textContent = name;

            document.getElementById('formEliminarRol').setAttribute(
                'action',
                url
            );
        });
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.hide();
        }
    }

    function crearAccionesRol(rol, urls) {
        return (
            '<div class="rol-actions">' +

            '<button type="button" ' +
            'class="btn btn-sm btn-outline-primary rol-action-btn" ' +
            'title="Editar rol" ' +
            'data-bs-toggle="modal" ' +
            'data-bs-target="#modalEditarRol" ' +
            'data-id="' + escapeAttribute(rol.id) + '" ' +
            'data-name="' + escapeAttribute(rol.name) + '" ' +
            'data-description="' + escapeAttribute(rol.description || '') + '" ' +
            'data-url="' + escapeAttribute(urls.update) + '">' +
            '<i class="fa-solid fa-pen"></i>' +
            '</button>' +

            '<button type="button" ' +
            'class="btn btn-sm btn-outline-danger rol-action-btn" ' +
            'title="Eliminar rol" ' +
            'data-bs-toggle="modal" ' +
            'data-bs-target="#modalEliminarRol" ' +
            'data-id="' + escapeAttribute(rol.id) + '" ' +
            'data-name="' + escapeAttribute(rol.name) + '" ' +
            'data-url="' + escapeAttribute(urls.delete) + '">' +
            '<i class="fa-solid fa-trash"></i>' +
            '</button>' +

            '</div>'
        );
    }

    function crearFilaRol(rol, urls, fechaRegistro) {
        return [
            '<span class="fw-semibold">' +
            escapeHtml(rol.name) +
            '</span>',

            rol.description
                ? escapeHtml(rol.description)
                : '<span class="text-secondary">Sin descripción</span>',

            '<span class="text-secondary">' +
            escapeHtml(fechaRegistro || '') +
            '</span>',

            crearAccionesRol(rol, urls)
        ];
    }

    function actualizarDatosAcciones(fila, rol, urls) {
        const botonEditar =
            fila.querySelector(
                '[data-bs-target="#modalEditarRol"]'
            );

        const botonEliminar =
            fila.querySelector(
                '[data-bs-target="#modalEliminarRol"]'
            );

        if (botonEditar) {
            botonEditar.dataset.id = rol.id;
            botonEditar.dataset.name = rol.name;
            botonEditar.dataset.description =
                rol.description || '';
            botonEditar.dataset.url = urls.update;
        }

        if (botonEliminar) {
            botonEliminar.dataset.id = rol.id;
            botonEliminar.dataset.name = rol.name;
            botonEliminar.dataset.url = urls.delete;
        }
    }

    function escaparHtml(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeHtml(valor) {
        return escaparHtml(valor);
    }

    function escapeAttribute(valor) {
        return escaparHtml(valor);
    }

    function ajustarFila(fila) {
        if (!fila) {
            return;
        }

        const celdas =
            fila.children;

        if (celdas.length < 4) {
            return;
        }

        celdas[0].style.width = '28%';
        celdas[1].style.width = '35%';
        celdas[2].style.width = '20%';
        celdas[3].style.width = '17%';

        celdas[3].classList.add(
            'text-end',
            'px-4'
        );

        const acciones =
            fila.querySelector(
                '.rol-actions'
            );

        if (acciones) {
            acciones.style.width = '100%';
            acciones.style.minWidth = '108px';
        }
    }

    function ajustarTodasLasFilas() {
        if (!tablaRoles) {
            return;
        }

        tablaRoles
            .rows()
            .every(function () {
                ajustarFila(
                    this.node()
                );
            });
    }

    function enviarFormulario(
        form,
        modal,
        mensajePorDefecto,
        callback
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

                if (callback) {
                    callback(data);
                }

                return data;
            })
            .catch((error) => {
                window.showToast(
                    'error',
                    error.message
                );

                throw error;
            })
            .finally(() => {
                if (botonSubmit) {
                    botonSubmit.disabled = false;

                    botonSubmit.innerHTML =
                        textoOriginal;
                }
            });
    }

    const formNuevoRol =
        document.getElementById(
            'formNuevoRol'
        );

    const formEditarRol =
        document.getElementById(
            'formEditarRol'
        );

    const formEliminarRol =
        document.getElementById(
            'formEliminarRol'
        );

    if (formNuevoRol) {
        formNuevoRol.addEventListener('submit', function (event) {
            event.preventDefault();

            enviarFormulario(
                formNuevoRol,
                modalNuevoRol,
                'Rol creado correctamente.',
                function (data) {
                    if (tablaRoles && data.rol) {
                        const fila = tablaRoles
                            .row.add(
                                crearFilaRol(data.rol, data.urls, data.fecha_registro)
                            )
                            .draw(false)
                            .node();

                        ajustarFila(fila);
                        ajustarTodasLasFilas();
                        tablaRoles.columns.adjust();
                    }

                    formNuevoRol.reset();
                }
            ).catch(() => { });
        });
    }

    if (formEditarRol) {
        formEditarRol.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const id =
                    document.getElementById(
                        'editar_id'
                    ).value;

                let filaDataTable = null;

                if (tablaRoles) {
                    tablaRoles
                        .rows()
                        .every(function () {
                            const fila =
                                this.node();

                            if (!fila) {
                                return;
                            }

                            const boton =
                                fila.querySelector(
                                    '[data-bs-target="#modalEditarRol"]'
                                );

                            if (
                                boton &&
                                boton.dataset.id === id
                            ) {
                                filaDataTable = this;
                            }
                        });
                }

                enviarFormulario(
                    formEditarRol,
                    modalEditarRol,
                    'Rol actualizado correctamente.',
                    function (data) {
                        if (
                            filaDataTable &&
                            data.rol
                        ) {
                            const fila =
                                filaDataTable.node();

                            const datos =
                                filaDataTable.data();

                            datos[0] =
                                '<span class="fw-semibold">' +
                                escapeHtml(
                                    data.rol.name
                                ) +
                                '</span>';

                            datos[1] =
                                data.rol.description
                                    ? escapeHtml(
                                        data.rol.description
                                    )
                                    : '<span class="text-secondary">Sin descripción</span>';

                            datos[2] =
                                '<span class="text-secondary">' +
                                escapeHtml(
                                    data.fecha_registro || ''
                                ) +
                                '</span>';

                            filaDataTable
                                .data(datos)
                                .draw(false);

                            actualizarDatosAcciones(
                                fila,
                                data.rol,
                                data.urls
                            );

                            ajustarFila(fila);

                            ajustarTodasLasFilas();
                        }
                    }
                ).catch(() => { });
            }
        );
    }

    if (formEliminarRol) {
        formEliminarRol.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                const id =
                    document.getElementById(
                        'eliminar_id'
                    ).value;

                let filaDataTable = null;

                if (tablaRoles) {
                    tablaRoles
                        .rows()
                        .every(function () {
                            const fila =
                                this.node();

                            if (!fila) {
                                return;
                            }

                            const boton =
                                fila.querySelector(
                                    '[data-bs-target="#modalEliminarRol"]'
                                );

                            if (
                                boton &&
                                boton.dataset.id === id
                            ) {
                                filaDataTable = this;
                            }
                        });
                }

                enviarFormulario(
                    formEliminarRol,
                    modalEliminarRol,
                    'Rol eliminado correctamente.',
                    function () {
                        if (filaDataTable) {
                            filaDataTable
                                .remove()
                                .draw(false);

                            ajustarTodasLasFilas();
                        }
                    }
                ).catch(() => { });
            }
        );
    }

    ajustarTodasLasFilas();
});
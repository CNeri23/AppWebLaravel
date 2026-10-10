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
                    const primerError = Object.values(data.errors).flat()[0];
                    throw new Error(primerError || data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
                }
                throw new Error(data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
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
                    targets: 3,
                    width: '20%'
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
    }

    function configurarTooltipsPaginacion() {
        const paginacion = document.querySelector('#tablaRoles_wrapper .dt-paging');

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

    const modalNuevo = document.getElementById('modalNuevoRol');
    const modalEditar = document.getElementById('modalEditarRol');
    const modalPermisos = document.getElementById('modalPermisosRol');
    const modalEliminar = document.getElementById('modalEliminarRol');

    const modalNuevoRol = modalNuevo ? new bootstrap.Modal(modalNuevo) : null;
    const modalEditarRol = modalEditar ? new bootstrap.Modal(modalEditar) : null;
    const modalPermisosRol = modalPermisos ? new bootstrap.Modal(modalPermisos) : null;
    const modalEliminarRol = modalEliminar ? new bootstrap.Modal(modalEliminar) : null;

    let datosOriginalesEditar = null;
    let permisosOriginales = null;

    const MENSAJE_SIN_CAMBIOS = 'No hubo cambios para actualizar.';

    function serializarPermisos(lista) {
        return lista
            .map(function (permiso) {
                return permiso.permission_type + ':' + permiso.permission_id;
            })
            .sort()
            .join('|');
    }

    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            datosOriginalesEditar = null;

            if (!button) {
                return;
            }
            const id = button.dataset.id;
            const name = button.dataset.name;
            const description = button.dataset.description;
            const url = button.dataset.url;

            datosOriginalesEditar = {
                name: (name || '').trim(),
                description: (description || '').trim(),
            };

            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            document.getElementById('editar_description').value = description || '';
            document.getElementById('formEditarRol').setAttribute('action', url);
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
            document.getElementById('formEliminarRol').setAttribute('action', url);
        });
    }

    const permisosTree = document.getElementById('permisosTree');

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

    function crearNodoModulo(modulo, permisos) {
        const id = 'permisoModulo_' + modulo.id;
        const childrenId = 'permisoModuloChildren_' + modulo.id;
        const seleccionado = permisos.has('modulo:' + modulo.id);
        let html = '';

        html +=
            '<div class="permisos-tree-node">';

        html +=
            '<div class="permisos-tree-row modulo">';

        html +=
            '<button ' + 'type="button" ' + 'class="permisos-tree-toggle" ' + 'data-tree-toggle="' + childrenId + '" ' +
            'title="Expandir / contraer">' + '<i class="fa-solid fa-chevron-down"></i>' +
            '</button>';

        html +=
            '<input ' + 'type="checkbox" ' + 'class="form-check-input permiso-checkbox" ' +
            'id="' + id + '" ' +
            'data-permission-type="modulo" ' +
            'data-permission-id="' + modulo.id + '" ' +
            (seleccionado ? 'checked ' : '') +'>';

        html +=
            '<span class="permisos-tree-icon">';

        if (modulo.icono) {
            html += modulo.icono;
        } else {
            html += '<i class="fa-solid fa-layer-group"></i>';
        }

        html += '</span>';

        html += '<label ' + 'class="permisos-tree-label mb-0" ' + 'for="' + id + '">' + escapeHtml(modulo.nombre);

        if (modulo.descripcion) {
            html += '<small>' + escapeHtml(modulo.descripcion) + '</small>';
        }
        html += '</label>';
        html += '</div>';
        html += '<div ' + 'class="permisos-tree-children" ' + 'id="' + childrenId + '">';

        if (modulo.submodulos && modulo.submodulos.length) {
            modulo.submodulos.forEach(function (submodulo) {
                html += crearNodoSubmodulo(submodulo, permisos);
            });
        }
        html += '</div>';
        html += '</div>';
        return html;
    }

    function crearNodoSubmodulo(submodulo, permisos) {
        const id = 'permisoSubmodulo_' + submodulo.id;
        const childrenId = 'permisoSubmoduloChildren_' + submodulo.id;
        const seleccionado = permisos.has('submodulo:' + submodulo.id);
        const tieneAcciones = submodulo.acciones && submodulo.acciones.length > 0;
        let html = '';
        html += '<div class="permisos-tree-node">';
        html += '<div class="permisos-tree-row submodulo">';

        if (tieneAcciones) {
            html +=
                '<button ' + 'type="button" ' + 'class="permisos-tree-toggle" ' + 'data-tree-toggle="' + childrenId + '" ' +
                'title="Expandir / contraer">' +
                '<i class="fa-solid fa-chevron-down"></i>' +
                '</button>';

        } else {
            html += '<span class="permisos-tree-spacer"></span>';
        }
        html +=
            '<input ' + 'type="checkbox" ' + 'class="form-check-input permiso-checkbox" ' +
            'id="' + id + '" ' +
            'data-permission-type="submodulo" ' +
            'data-permission-id="' + submodulo.id + '" ' +
            (seleccionado ? 'checked ' : '') + '>';

        html += '<span class="permisos-tree-icon">';

        if (submodulo.icono) {
            html += submodulo.icono;
        } else {
            html += '<i class="fa-solid fa-circle-dot"></i>';
        }
        html += '</span>';
        html +='<label ' + 'class="permisos-tree-label mb-0" ' + 'for="' + id + '">' + escapeHtml(submodulo.nombre);

        if (submodulo.descripcion) {
            html += '<small>' + escapeHtml(submodulo.descripcion) + '</small>';
        }
        html += '</label>';
        html += '</div>';

        if (tieneAcciones) {
            html += '<div ' + 'class="permisos-tree-children" ' + 'id="' + childrenId + '">';
            submodulo.acciones.forEach(function (accion) {
                html += crearNodoAccion(accion, permisos);
            });
            html += '</div>';
        }
        html += '</div>';
        return html;
    }

    function crearNodoAccion(accion, permisos) {
        const id = 'permisoAccion_' + accion.id;
        const seleccionado = permisos.has('accion:' + accion.id);

        let html = '';
        html += '<div class="permisos-tree-node">';
        html += '<div class="permisos-tree-row accion">';
        html += '<span class="permisos-tree-spacer"></span>';
        html +=
            '<input ' + 'type="checkbox" ' + 'class="form-check-input permiso-checkbox" ' +
            'id="' + id + '" ' +
            'data-permission-type="accion" ' +
            'data-permission-id="' + accion.id + '" ' +
            (seleccionado ? 'checked ' : '') + '>';
        html += '<span class="permisos-tree-icon">';

        if (accion.icono) {
            html += accion.icono;
        } else {
            html += '<i class="fa-solid fa-bolt"></i>';
        }
        html += '</span>';
        html += '<label ' + 'class="permisos-tree-label mb-0" ' + 'for="' + id + '">' + escapeHtml(accion.nombre);

        if (accion.descripcion) {
            html += '<small>' + escapeHtml(accion.descripcion) + '</small>';
        }
        html += '</label>';
        html += '</div>';
        html += '</div>';
        return html;
    }

    function crearPermisosSet(permisos) {
        const resultado = new Set();

        permisos.forEach(function (permiso) {
            resultado.add(permiso.permission_type + ':' + permiso.permission_id);
        });

        return resultado;
    }

    function renderizarTreeview(modulos, permisos) {
        if (!permisosTree) {
            return;
        }

        if (!modulos || !modulos.length) {
            permisosTree.innerHTML =
                '<div class="text-center text-secondary py-4">' +
                '<i class="fa-solid fa-folder-open fa-lg mb-2"></i>' +
                '<div>No hay permisos disponibles.</div>' +
                '</div>';
            return;
        }

        const permisosSet = crearPermisosSet(permisos);
        let html = '';
        modulos.forEach(function (modulo) {
            html += crearNodoModulo(modulo, permisosSet);
        });
        permisosTree.innerHTML = html;
    }

    function obtenerPermisosSeleccionados() {
        const permisos = [];

        if (!permisosTree) {
            return permisos;
        }

        permisosTree.querySelectorAll('.permiso-checkbox:checked').forEach(function (checkbox) {
                permisos.push({permission_type: checkbox.dataset.permissionType,
                    permission_id:
                        Number(
                            checkbox.dataset.permissionId
                        )
                });
            });
        return permisos;
    }

    function prepararTreeview() {
        if (!permisosTree) {
            return;
        }

        permisosTree.addEventListener('click',
            function (event) {
                const toggle = event.target.closest('[data-tree-toggle]');

                if (!toggle) {
                    return;
                }
                const targetId = toggle.dataset.treeToggle;
                const target = document.getElementById(targetId);

                if (!target) {
                    return;
                }

                const oculto = target.classList.toggle('collapsed');
                toggle.classList.toggle('collapsed',oculto);
            }
        );
    }

    prepararTreeview();

    if (modalPermisos) {
        modalPermisos.addEventListener('show.bs.modal',
            function (event) {
                const button = event.relatedTarget;
                permisosOriginales = null;

                if (!button) {
                    return;
                }
                const id = button.dataset.id;
                const name = button.dataset.name;
                const url = button.dataset.url;
                const saveUrl =button.dataset.saveUrl;

                document.getElementById('permisos_rol_id').value = id;
                document.getElementById('permisos_rol_nombre').textContent = name;
                document.getElementById('formPermisosRol').dataset.saveUrl = saveUrl;

                if (permisosTree) {
                    permisosTree.innerHTML =
                        '<div class="permisos-tree-loading text-center py-4">' +
                        '<div class="spinner-border spinner-border-sm text-primary mb-2" role="status"></div>' +
                        '<div class="text-secondary">Cargando permisos...</div>' +
                        '</div>';
                }

                peticion(url, 'GET')
                    .then(function (data) {
                        renderizarTreeview(data.modulos, data.permisos);
                        permisosOriginales = serializarPermisos(obtenerPermisosSeleccionados());
                    })
                    .catch(function (error) {

                        if (permisosTree) {
                            permisosTree.innerHTML =
                                '<div class="text-center text-danger py-4">' +
                                '<i class="fa-solid fa-circle-exclamation fa-lg mb-2"></i>' +
                                '<div>' +
                                escapeHtml(error.message) +
                                '</div>' +
                                '</div>';
                        }

                        window.showToast('error', error.message);
                    });
            }
        );
    }

    const formPermisosRol = document.getElementById('formPermisosRol');

    if (formPermisosRol) {
        formPermisosRol.addEventListener('submit',
            function (event) {
                event.preventDefault();
                const botonSubmit = document.getElementById('btnGuardarPermisos');
                const textoOriginal = botonSubmit?.innerHTML;
                const saveUrl = formPermisosRol.dataset.saveUrl;

                if (!saveUrl) {
                    window.showToast('error', 'No se encontró la ruta para guardar los permisos.');
                    return;
                }
                const permisos = obtenerPermisosSeleccionados();

                if (permisosOriginales === null) {
                    window.showToast('warning', 'Espera a que terminen de cargar los permisos.');
                    return;
                }

                if (serializarPermisos(permisos) === permisosOriginales) {
                    window.showToast('info', MENSAJE_SIN_CAMBIOS);
                    return;
                }

                if (botonSubmit) {
                    botonSubmit.disabled = true;
                    botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 'Guardando...';
                }

                peticion(saveUrl, 'PUT', JSON.stringify({permisos}))
                    .then(function (data) {
                        permisosOriginales = serializarPermisos(permisos);

                        if (modalPermisosRol) {
                            modalPermisosRol.hide();
                        }
                        window.showToast('success', data.mensaje || 'Permisos actualizados correctamente.');

                        if (data.acciones_roles) {
                            window.accionesRoles = data.acciones_roles;
                            actualizarBotonNuevoRol();

                            if (tablaRoles) {
                                tablaRoles
                                    .rows()
                                    .every(function () {
                                        const filaDataTable = this;
                                        const fila = filaDataTable.node();

                                        if (!fila) {
                                            return;
                                        }
                                        const datos = filaDataTable.data();

                                        if (!datos ||datos.length < 4) {
                                            return;
                                        }
                                        const botonEditar = fila.querySelector('[data-bs-target="#modalEditarRol"]');
                                        const botonPermisos = fila.querySelector('[data-bs-target="#modalPermisosRol"]');
                                        const botonEliminar = fila.querySelector('[data-bs-target="#modalEliminarRol"]');

                                        let id = '';
                                        let name = '';
                                        let description = '';
                                        let urls = {update: '', delete: '', permisos: '', actualizarPermisos: ''};

                                        if (botonEditar) {
                                            id = botonEditar.dataset.id ||'';
                                            name = botonEditar.dataset.name || '';
                                            description = botonEditar.dataset.description || '';
                                            urls.update = botonEditar.dataset.url || '';
                                        }

                                        if (botonPermisos) {
                                            id = botonPermisos.dataset.id || id;
                                            name = botonPermisos.dataset.name || name;
                                            urls.permisos = botonPermisos.dataset.url ||'';
                                            urls.actualizarPermisos = botonPermisos.dataset.saveUrl ||'';
                                        }

                                        if (botonEliminar) {
                                            id = botonEliminar.dataset.id || id;
                                            name = botonEliminar.dataset.name || name;
                                            urls.delete = botonEliminar.dataset.url || '';
                                        }

                                        const nombreContenedor = document.createElement('div');
                                        nombreContenedor.innerHTML = datos[0];
                                        const nombreElemento = nombreContenedor.textContent.trim();
                                        const descripcionContenedor = document.createElement('div');
                                        descripcionContenedor.innerHTML = datos[1];
                                        const descripcionElemento = descripcionContenedor.textContent.trim();

                                        if (!name) {
                                            name = nombreElemento;
                                        }

                                        if (!description) {
                                            description = descripcionElemento === 'Sin descripción' ? '': descripcionElemento;
                                        }

                                        if (!id) {
                                            const nombre =nombreContenedor.querySelector('[data-role-id]');
                                            if (nombre) {
                                                id =nombre.dataset.roleId || '';
                                            }
                                        }

                                        if (!id) {
                                            return;
                                        }

                                        if (!urls.update || !urls.delete || !urls.permisos || !urls.actualizarPermisos) {
                                            const boton = fila.querySelector('.rol-action-btn');

                                            if (boton) {
                                                if (!urls.update) {
                                                    urls.update = boton.dataset.url ||'';
                                                }

                                                if (!urls.delete) {
                                                    urls.delete = boton.dataset.url || '';
                                                }

                                                if (!urls.permisos) {
                                                    urls.permisos = boton.dataset.url || '';
                                                }

                                                if (!urls.actualizarPermisos) {
                                                    urls.actualizarPermisos = boton.dataset.saveUrl || '';
                                                }
                                            }
                                        }

                                        datos[3] = crearAccionesRol({id: id, name: name, description: description}, urls);
                                        filaDataTable.data(datos).draw(false);
                                        const nuevaFila = filaDataTable.node();
                                        ajustarFila(nuevaFila);
                                    });
                                ajustarTodasLasFilas();
                                tablaRoles.columns.adjust();
                            }
                        }

                        if (
                            typeof window.actualizarSidebar === 'function'
                        ) {
                            window.actualizarSidebar();
                        }

                    })
                    .catch(function (error) {
                        window.showToast('error', error.message);
                    })
                    .finally(function () {
                        if (botonSubmit) {
                            botonSubmit.disabled = false;
                            botonSubmit.innerHTML = textoOriginal;
                        }
                    });
            }
        );
    }

    function actualizarBotonNuevoRol() {
        const contenedor = document.querySelector('.d-flex.justify-content-between.align-items-center.mb-4');

        if (!contenedor) {
            return;
        }
        const acciones = window.accionesRoles || [];
        const accionCrear =
            acciones.find(function (accion) {
                return accion.slug === 'roles.crear';
            });
        const botonExistente = document.getElementById('btnNuevoRol');

        if (!accionCrear) {
            if (botonExistente) {
                botonExistente.remove();
            }
            return;
        }

        if (botonExistente) {
            botonExistente.title = accionCrear.nombre;
            botonExistente.innerHTML = accionCrear.icono || '<i class="fa-solid fa-user-plus me-2"></i>';
            botonExistente.appendChild(document.createTextNode(' ' + accionCrear.nombre));
            return;
        }
        const boton = document.createElement('button');
        boton.type = 'button';
        boton.id = 'btnNuevoRol';
        boton.className = 'btn btn-primary';
        boton.setAttribute('data-bs-toggle', 'modal');
        boton.setAttribute('data-bs-target', '#modalNuevoRol');
        boton.title = accionCrear.nombre;
        boton.innerHTML = accionCrear.icono || '<i class="fa-solid fa-user-plus me-2"></i>';
        boton.appendChild(document.createTextNode(' ' + accionCrear.nombre));
        contenedor.appendChild(boton);
    }

    function crearAccionesRol(rol, urls) {
        let html = '<div class="rol-actions">';
        const acciones = window.accionesRoles || [];
        acciones.forEach(function (accion) {

            if (accion.slug === 'roles.editar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-primary rol-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEditarRol" ' +
                    'data-id="' + escapeAttribute(rol.id) + '" ' +
                    'data-name="' + escapeAttribute(rol.name) + '" ' +
                    'data-description="' + escapeAttribute(rol.description || '') + '" ' +
                    'data-url="' + escapeAttribute(urls.update) + '">' +
                    (accion.icono || '<i class="fa-solid fa-pen"></i>') +
                    '</button>';
            }
            else if (accion.slug === 'roles.permisos') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-success rol-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalPermisosRol" ' +
                    'data-id="' + escapeAttribute(rol.id) + '" ' +
                    'data-name="' + escapeAttribute(rol.name) + '" ' +
                    'data-url="' + escapeAttribute(urls.permisos) + '" ' +
                    'data-save-url="' + escapeAttribute(urls.actualizarPermisos) + '">' +
                    (accion.icono || '<i class="fa-solid fa-key"></i>') +
                    '</button>';

            }
            else if (accion.slug === 'roles.eliminar') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger rol-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalEliminarRol" ' +
                    'data-id="' + escapeAttribute(rol.id) + '" ' +
                    'data-name="' + escapeAttribute(rol.name) + '" ' +
                    'data-url="' + escapeAttribute(urls.delete) + '">' +
                    (accion.icono || '<i class="fa-solid fa-trash"></i>') +
                    '</button>';

            }

        });
        html += '</div>';
        return html;
    }

    function crearFilaRol(rol, urls, fechaRegistro) {
        return [
            '<span ' + 'class="fw-semibold" ' +
            'data-role-id="' + escapeAttribute(rol.id) + '" ' +
            'data-role-name="' + escapeAttribute(rol.name) + '" ' +
            'data-role-description="' + escapeAttribute(rol.description || '') + '">' + escapeHtml(rol.name) +
            '</span>',
            rol.description ? escapeHtml(rol.description) : '<span class="text-secondary">Sin descripción</span>',
            '<span class="text-secondary">' +  escapeHtml(fechaRegistro || '') + '</span>',
            crearAccionesRol(rol, urls)
        ];
    }

    function actualizarDatosAcciones(fila, rol, urls) {
        if (!fila) {
            return;
        }
        const celdas = fila.children;

        if (celdas.length < 4) {
            return;
        }
        const botonDe = function (selector) {
            return fila.querySelector(selector);
        };

        const previas = {
            update: botonDe('[data-bs-target="#modalEditarRol"]')?.dataset.url || '',
            permisos: botonDe('[data-bs-target="#modalPermisosRol"]')?.dataset.url || '',
            actualizarPermisos: botonDe('[data-bs-target="#modalPermisosRol"]')?.dataset.saveUrl || '',
            delete: botonDe('[data-bs-target="#modalEliminarRol"]')?.dataset.url || '',
        };

        const urlsFinales = {
            update: urls?.update || previas.update,
            permisos: urls?.permisos || previas.permisos,
            actualizarPermisos: urls?.actualizarPermisos || previas.actualizarPermisos,
            delete: urls?.delete || previas.delete,
        };

        if ((window.accionesRoles || []).length > 0) {
            celdas[3].innerHTML =
                crearAccionesRol(rol, urlsFinales);
        } else {
            celdas[3]
                .querySelectorAll('.rol-action-btn')
                .forEach(function (boton) {
                    boton.dataset.name = rol.name;
                    if (boton.dataset.description !== undefined) {
                        boton.dataset.description = rol.description || '';
                    }
                });
        }
        celdas[3].classList.add(
            'px-4'
        );
    }


    function ajustarFila(fila) {
        if (!fila) {
            return;
        }

        const celdas = fila.children;

        if (celdas.length < 4) {
            return;
        }

        celdas[0].style.width = '27%';
        celdas[1].style.width = '33%';
        celdas[2].style.width = '20%';
        celdas[3].style.width = '20%';
        celdas[3].classList.add('px-4');
    }

    function ajustarTodasLasFilas() {
        if (!tablaRoles) {
            return;
        }
        tablaRoles.rows().every(function () {ajustarFila(this.node());});
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.hide();
        }
    }

    function enviarFormulario(form, modal, mensajePorDefecto, callback) {
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

                if (callback) {
                    callback(data);
                }
                return data;
            })
            .catch((error) => {
                window.showToast('error', error.message);
                throw error;
            })
            .finally(() => {
                if (botonSubmit) {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                }
            });
    }

    const formNuevoRol =document.getElementById('formNuevoRol');

    if (formNuevoRol) {
        formNuevoRol.addEventListener('submit', function (event) {
            event.preventDefault();

            enviarFormulario(formNuevoRol, modalNuevoRol, 'Rol creado correctamente.',
                function (data) {

                    if (tablaRoles && data.rol) {
                        const fila = tablaRoles.row.add(crearFilaRol(data.rol, data.urls, data.fecha_registro)).draw(false).node();
                        ajustarFila(fila);
                        ajustarTodasLasFilas();
                        tablaRoles.columns.adjust();
                    }
                    formNuevoRol.reset();
                }
            ).catch(() => { });
        });
    }

    const formEditarRol = document.getElementById('formEditarRol');

    if (formEditarRol) {
        formEditarRol.addEventListener('submit',
            function (event) {
                event.preventDefault();
                const nombreActual = document.getElementById('editar_name').value.trim();
                const descripcionActual = document.getElementById('editar_description').value.trim();

                if (datosOriginalesEditar && nombreActual === datosOriginalesEditar.name && descripcionActual === datosOriginalesEditar.description
                ) {
                    window.showToast('info', MENSAJE_SIN_CAMBIOS);
                    return;
                }

                const id = document.getElementById('editar_id').value;
                let filaDataTable = null;

                if (tablaRoles) {
                    tablaRoles
                        .rows()
                        .every(function () {
                            const fila = this.node();

                            if (!fila) {
                                return;
                            }

                            const boton = fila.querySelector('[data-bs-target="#modalEditarRol"]');

                            if (boton && boton.dataset.id === id) {
                                filaDataTable = this;
                            }
                        });
                }

                enviarFormulario(formEditarRol, modalEditarRol, 'Rol actualizado correctamente.',
                    function (data) {
                        if (filaDataTable &&data.rol) {
                            const fila = filaDataTable.node();
                            const datos = filaDataTable.data();

                            datos[0] = 
                                '<span ' + 'class="fw-semibold" ' + 'data-role-id="' + escapeAttribute(data.rol.id) + '" ' +
                                'data-role-name="' + escapeAttribute(data.rol.name) + '" ' +
                                'data-role-description="' + escapeAttribute(data.rol.description || '') + '">' +escapeHtml(data.rol.name) +
                                '</span>';
                            datos[1] =  data.rol.description ? escapeHtml(data.rol.description) : '<span class="text-secondary">Sin descripción</span>';
                            datos[2] = '<span class="text-secondary">' + escapeHtml(data.fecha_registro || '') + '</span>';

                            filaDataTable.data(datos).draw(false);

                            actualizarDatosAcciones(filaDataTable.node(), data.rol,data.urls);
                            ajustarFila(filaDataTable.node());
                            filaDataTable.invalidate('dom').draw(false);
                            ajustarTodasLasFilas();
                        }
                    }
                ).catch(() => { });
            }
        );
    }

    const formEliminarRol = document.getElementById('formEliminarRol');

    if (formEliminarRol) {
        formEliminarRol.addEventListener('submit',
            function (event) {
                event.preventDefault();
                const id = document.getElementById('eliminar_id').value;
                let filaDataTable = null;

                if (tablaRoles) {
                    tablaRoles
                        .rows()
                        .every(function () {
                            const fila = this.node();

                            if (!fila) {
                                return;
                            }
                            const boton = fila.querySelector('[data-bs-target="#modalEliminarRol"]');
                            if (boton && boton.dataset.id === id) {
                                filaDataTable = this;
                            }
                        });
                }

                enviarFormulario(formEliminarRol, modalEliminarRol, 'Rol eliminado correctamente.',
                    function () {
                        if (filaDataTable) {
                            filaDataTable.remove().draw(false);
                            ajustarTodasLasFilas();
                        }
                    }
                ).catch(() => { });
            }
        );
    }
    ajustarTodasLasFilas();
});
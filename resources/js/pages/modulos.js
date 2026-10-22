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

    const REGEX_ICONO = /^<i\s+class="\s*fa-(?:solid|regular|brands)(?:\s+fa-[a-z0-9]+(?:-[a-z0-9]+)*)+\s*"\s*>\s*<\/i>$/;
    const MENSAJE_ICONO = 'El ícono no tiene el formato requerido. Ejemplo: ' + '<i class="fa-solid fa-users"></i>';
    const FORMULARIOS_SELECTOR = 'form[id^="formNuevo"], form[id^="formNueva"], form[id^="formEditar"]';
    const CAMPOS_SELECTOR = 'input:not([type="hidden"]):not([readonly]):not([type="button"]):not([type="submit"]), textarea';
    const instantaneasFormulario = new WeakMap();

    function camposDelFormulario(form) {
        return Array.from(form.querySelectorAll(CAMPOS_SELECTOR));
    }

    function esFormularioEdicion(form) {
        return (form.getAttribute('id') || '').startsWith('formEditar');
    }

    function limpiarValidacionCampo(campo) {
        campo.classList.remove('is-invalid');
        campo.setCustomValidity('');
        campo.parentElement ?.querySelectorAll('.js-campo-feedback').forEach((el) => el.remove());
    }

    function mensajeDeCampo(campo) {
        const valor = campo.value.trim();

        if (valor === '') {
            return 'Este campo es obligatorio.';
        }

        if (campo.name === 'icono' && !REGEX_ICONO.test(valor)) {
            return MENSAJE_ICONO;
        }

        if (campo.type === 'number' && !/^\d+$/.test(valor)) {
            return 'Ingresa un número entero mayor o igual a 0.';
        }
        return '';
    }

    function validarCampo(campo) {
        limpiarValidacionCampo(campo);

        const mensaje = mensajeDeCampo(campo);

        if (!mensaje) {
            return true;
        }

        campo.classList.add('is-invalid');
        campo.setCustomValidity(mensaje);
        const feedback = document.createElement('div');
        feedback.className = 'invalid-feedback js-campo-feedback';
        feedback.textContent = mensaje;
        campo.insertAdjacentElement('afterend', feedback);
        return false;
    }

    function validarFormulario(form) {
        let primerInvalido = null;
        camposDelFormulario(form).forEach((campo) => {
            if (!validarCampo(campo) && !primerInvalido) {
                primerInvalido = campo;
            }
        });

        if (primerInvalido) {
            primerInvalido.focus();
            return false;
        }
        camposDelFormulario(form).forEach((campo) => {
            campo.value = campo.value.trim();
        });
        return true;
    }

    function valoresDelFormulario(form) {
        const valores = {};

        camposDelFormulario(form).forEach((campo) => {
            valores[campo.name] = campo.value.trim();
        });
        return valores;
    }

    function hayCambios(form) {
        const antes = instantaneasFormulario.get(form);

        if (!antes) {
            return true;
        }

        const ahora = valoresDelFormulario(form);
        return Object.keys(ahora).some(
            (clave) => ahora[clave] !== antes[clave]
        );
    }

    function sincronizarCarpeta(item, expandido) {
        const icono = item.querySelector(':scope > .tree-folder i');

        if (!icono) {
            return;
        }

        icono.classList.toggle('fa-folder-open', expandido);
        icono.classList.toggle('fa-folder', !expandido);
    }

    function actualizarSidebar() {
        if (typeof window.actualizarSidebar === 'function') {
            return window.actualizarSidebar();
        }
        return Promise.resolve();
    }

    // Actualiza un enlace conservando su nodo para evitar parpadeos y mantener listeners.
    function sincronizarEnlaceSidebar(actual, nuevo) {
        actual.href = nuevo.getAttribute('href') || '#';
        actual.className = nuevo.className;

        if (nuevo.hasAttribute('title')) {
            actual.setAttribute('title', nuevo.getAttribute('title'));
        } else {
            actual.removeAttribute('title');
        }

        actual.innerHTML = nuevo.innerHTML;
    }

    // Sincroniza únicamente el elemento afectado del sidebar, sin reemplazar todo el menú.
    function actualizarElementoSidebar(tipo, id, conservarNodos = false) {
        const sidebar = document.querySelector('#appSidebar .sidebar-menu');
        if (!sidebar) {
            return Promise.resolve();
        }

        return fetch(window.location.href, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        }).then((response) => {
            if (!response.ok) {
                throw new Error('No fue posible sincronizar el menú lateral.');
            }
            return response.text();
        }).then((html) => {
            const documento = new DOMParser().parseFromString(html, 'text/html');
            const nuevoMenu = documento.querySelector('#appSidebar .sidebar-menu');
            if (!nuevoMenu) {
                throw new Error('No fue posible encontrar el menú lateral.');
            }

            if (tipo === 'modulo') {
                const selector = '.sidebar-group-toggle[data-modulo-id="' + id + '"]';
                const actualToggle = sidebar.querySelector(selector);
                const nuevoToggle = nuevoMenu.querySelector(selector);
                const grupoActual = actualToggle?.closest('.sidebar-group');
                const grupoNuevo = nuevoToggle?.closest('.sidebar-group');

                if (grupoActual && grupoNuevo) {
                    const submenuActual = grupoActual.querySelector('.sidebar-submenu');
                    const submenuNuevo = grupoNuevo.querySelector('.sidebar-submenu');
                    const abierto = Boolean(submenuActual?.classList.contains('show'));

                    if (conservarNodos) {
                        const iconoActual = actualToggle.querySelector(':scope > i:not(.sidebar-group-caret)');
                        const iconoNuevo = nuevoToggle.querySelector(':scope > i:not(.sidebar-group-caret)');
                        const nombreActual = actualToggle.querySelector(':scope > span');
                        const nombreNuevo = nuevoToggle.querySelector(':scope > span');

                        if (iconoActual && iconoNuevo) {
                            iconoActual.replaceWith(iconoNuevo.cloneNode(true));
                        } else if (iconoActual && !iconoNuevo) {
                            iconoActual.remove();
                        } else if (!iconoActual && iconoNuevo) {
                            actualToggle.insertBefore(iconoNuevo.cloneNode(true), nombreActual || actualToggle.firstChild);
                        }

                        if (nombreActual && nombreNuevo) {
                            nombreActual.textContent = nombreNuevo.textContent;
                        }
                        actualToggle.dataset.label = nuevoToggle.dataset.label || '';
                        actualToggle.classList.toggle('active', nuevoToggle.classList.contains('active'));

                        if (submenuActual && submenuNuevo) {
                            submenuActual.querySelectorAll('.sidebar-sublink').forEach((linkActual) => {
                                const linkNuevo = submenuNuevo.querySelector('.sidebar-sublink[data-submodulo-id="' + linkActual.dataset.submoduloId + '"]');
                                if (!linkNuevo) {
                                    linkActual.remove();
                                    return;
                                }
                                sincronizarEnlaceSidebar(linkActual, linkNuevo);
                            });
                        }
                    } else {
                        grupoActual.replaceWith(grupoNuevo.cloneNode(true));
                        const grupoInsertado = sidebar.querySelector(selector)?.closest('.sidebar-group');
                        const submenuInsertado = grupoInsertado?.querySelector('.sidebar-submenu');
                        if (abierto && submenuInsertado) {
                            submenuInsertado.classList.add('show');
                            const boton = grupoInsertado.querySelector('.sidebar-group-toggle');
                            boton?.setAttribute('aria-expanded', 'true');
                        }
                    }
                } else if (grupoActual && !grupoNuevo) {
                    grupoActual.remove();
                } else if (!grupoActual && grupoNuevo) {
                    const gruposNuevos = Array.from(nuevoMenu.querySelectorAll('.sidebar-group'));
                    const indice = gruposNuevos.findIndex((grupo) => grupo.querySelector(selector));
                    const siguienteId = gruposNuevos.slice(indice + 1)
                        .map((grupo) => grupo.querySelector('.sidebar-group-toggle')?.dataset.moduloId)
                        .find((moduloId) => moduloId && sidebar.querySelector('.sidebar-group-toggle[data-modulo-id="' + moduloId + '"]'));
                    const siguienteGrupo = siguienteId
                        ? sidebar.querySelector('.sidebar-group-toggle[data-modulo-id="' + siguienteId + '"]')?.closest('.sidebar-group')
                        : null;
                    const administracion = Array.from(sidebar.querySelectorAll('.sidebar-section'))
                        .find((seccion) => seccion.textContent.trim() === 'Administración');
                    if (siguienteGrupo) {
                        siguienteGrupo.before(grupoNuevo.cloneNode(true));
                    } else if (administracion) {
                        let ultimoGrupo = administracion.nextElementSibling;
                        while (ultimoGrupo && ultimoGrupo.classList.contains('sidebar-group')) {
                            ultimoGrupo = ultimoGrupo.nextElementSibling;
                        }
                        if (ultimoGrupo) {
                            ultimoGrupo.before(grupoNuevo.cloneNode(true));
                        } else {
                            sidebar.append(grupoNuevo.cloneNode(true));
                        }
                    } else {
                        sidebar.append(grupoNuevo.cloneNode(true));
                    }
                }
            } else {
                const selector = '.sidebar-sublink[data-submodulo-id="' + id + '"]';
                const actualLink = sidebar.querySelector(selector);
                const nuevoLink = nuevoMenu.querySelector(selector);
                const contenedorActual = actualLink?.closest('.sidebar-submenu');
                const contenedorNuevo = nuevoLink?.closest('.sidebar-submenu');
                if (actualLink && nuevoLink) {
                    if (conservarNodos) {
                        sincronizarEnlaceSidebar(actualLink, nuevoLink);
                    } else {
                        actualLink.replaceWith(nuevoLink.cloneNode(true));
                    }
                } else if (actualLink && !nuevoLink) {
                    actualLink.remove();
                } else if (!actualLink && nuevoLink && contenedorNuevo) {
                    const moduloId = contenedorNuevo.dataset.moduloId;
                    const contenedorDestino = sidebar.querySelector('.sidebar-submenu[data-modulo-id="' + moduloId + '"]');
                    if (contenedorDestino) {
                        const nuevosLinks = Array.from(contenedorNuevo.querySelectorAll('.sidebar-sublink'));
                        const indice = nuevosLinks.findIndex((link) => link.dataset.submoduloId === String(id));
                        const siguienteId = nuevosLinks.slice(indice + 1)
                            .map((link) => link.dataset.submoduloId)
                            .find((subId) => contenedorDestino.querySelector('.sidebar-sublink[data-submodulo-id="' + subId + '"]'));
                        const siguienteLink = siguienteId
                            ? contenedorDestino.querySelector('.sidebar-sublink[data-submodulo-id="' + siguienteId + '"]')
                            : null;
                        if (siguienteLink) {
                            siguienteLink.before(nuevoLink.cloneNode(true));
                        } else {
                            contenedorDestino.append(nuevoLink.cloneNode(true));
                        }
                    } else {
                        // Si el módulo padre tampoco existe, sincronizar solo ese grupo.
                        return actualizarElementoSidebar('modulo', moduloId);
                    }
                }
            }

            // En ediciones solo cambiamos el contenido del nodo existente.
            // Reinicializar todos los grupos aquí vuelve a abrir/cerrar los submenús
            // y provoca el parpadeo que no ocurre al activar o desactivar.
            if (!conservarNodos && typeof window.inicializarGruposSidebar === 'function') {
                window.inicializarGruposSidebar();
            }
        });
    }

    function obtenerTreeview() {
        const treeview = document.getElementById('modulosTree');
        if (!treeview) {
            return null;
        }
        return treeview;
    }

    function actualizarTreeview() {
        const treeview = obtenerTreeview();

        if (!treeview) {
            return Promise.resolve();
        }

        const modulosExpandidos = Array.from(document.querySelectorAll('.tree-node.expanded > .modulo-tree-item')).map((item) => item.dataset.id);
        const moduloSeleccionado =document.querySelector('.modulo-tree-item.selected')?.dataset.id || null;
        const submodulosExpandidos = Array.from(document.querySelectorAll('.tree-child-node.expanded > .submodulo-tree-item')).map((item) => item.dataset.id);
        const submoduloSeleccionado =document.querySelector('.submodulo-tree-item.selected')?.dataset.id || null;
        const accionSeleccionada =document.querySelector('.accion-tree-item.selected')?.dataset.id || null;
        cerrarMenu();

        return fetch(window.location.href, {
            method: 'GET',
            headers: {
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
            cache: 'no-store',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('No fue posible actualizar la lista.');
                }
                return response.text();
            })
            .then((html) => {
                const documento = new DOMParser().parseFromString(html, 'text/html');
                const nuevoTreeview = documento.getElementById('modulosTree');

                if (!nuevoTreeview) {
                    throw new Error('No fue posible encontrar la lista de módulos.');
                }

                treeview.replaceWith(nuevoTreeview);
                inicializarTreeview();

                modulosExpandidos.forEach((id) => {
                    const item = document.querySelector('.modulo-tree-item[data-id="' + CSS.escape(id) +'"]');

                    if (!item) {
                        return;
                    }
                    const nodo = item.closest('.tree-node');

                    if (!nodo) {
                        return;
                    }
                    const submenu = nodo.querySelector(':scope > .tree-children');
                    const folderIcon = item.querySelector('.tree-folder i');

                    if (!submenu) {
                        return;
                    }
                    nodo.classList.add('expanded');

                    if (folderIcon) {
                        folderIcon.classList.remove(
                            'fa-folder'
                        );

                        folderIcon.classList.add(
                            'fa-folder-open'
                        );
                    }
                });

                submodulosExpandidos.forEach((id) => {
                    const item = document.querySelector('.submodulo-tree-item[data-id="' + CSS.escape(id) + '"]');

                    if (!item) {
                        return;
                    }
                    const nodo = item.closest('.tree-child-node');

                    if (!nodo) {
                        return;
                    }
                    const submenu = nodo.querySelector(':scope > .action-children');

                    if (!submenu) {
                        return;
                    }
                    nodo.classList.add('expanded');
                    sincronizarCarpeta(item, true);
                });

                if (moduloSeleccionado) {
                    const item = document.querySelector('.modulo-tree-item[data-id="' + CSS.escape(moduloSeleccionado) + '"]');

                    item?.classList.add('selected');
                }

                if (submoduloSeleccionado) {
                    const item = document.querySelector('.submodulo-tree-item[data-id="' + CSS.escape(submoduloSeleccionado) + '"]');
                    item?.classList.add('selected');
                }

                if (accionSeleccionada) {
                    const item = document.querySelector('.accion-tree-item[data-id="' + CSS.escape(accionSeleccionada) + '"]');
                    item?.classList.add('selected');
                }
            });
    }

    let items = [];
    let submoduloItems = [];
    let accionItems = [];

    function seleccionar(item) {
        items.forEach((el) => {el.classList.remove('selected');});
        submoduloItems.forEach((el) => {el.classList.remove('selected');});
        accionItems.forEach((el) => {el.classList.remove('selected');});
        item.classList.add('selected');
    }

    function alternarModulo(item) {
        const nodo = item.closest('.tree-node');

        if (!nodo) {
            return;
        }
        const submenu = nodo.querySelector(':scope > .tree-children');
        const folderIcon = item.querySelector('.tree-folder i');

        if (!submenu) {
            return;
        }
        const expandido = nodo.classList.toggle('expanded');

        if (folderIcon) {
            folderIcon.classList.toggle(
                'fa-folder-open',
                expandido
            );

            folderIcon.classList.toggle(
                'fa-folder',
                !expandido
            );
        }
    }

    function alternarSubmodulo(item) {
        const nodo = item.closest('.tree-child-node');

        if (!nodo) {
            return;
        }
        const submenu = nodo.querySelector(':scope > .action-children');

        if (!submenu) {
            return;
        }
        const expandido = nodo.classList.toggle('expanded');
        sincronizarCarpeta(item, expandido);
    }

    function toggleModulo(item) {
        const url = item.dataset.toggleUrl;

        return peticion(url, 'PATCH')
            .then((data) => {
                item.dataset.activo = data.activo ? '1' : '0';
                item.classList.toggle('is-inactive', !data.activo);
                const folderIcon = item.querySelector('.tree-folder i');

                if (folderIcon) {
                    const nodo = item.closest('.tree-node');
                    const expandido = nodo?.classList.contains('expanded');
                    folderIcon.classList.toggle('fa-folder-open', expandido && data.activo);
                    folderIcon.classList.toggle('fa-folder', !expandido || !data.activo);
                }
                let badge = item.querySelector('.tree-badge');

                if (!data.activo && !badge) {
                    badge = document.createElement('span');
                    badge.className = 'tree-badge';
                    badge.textContent = 'Inactivo';
                    item.querySelector('.tree-name') ?.insertAdjacentElement('afterend', badge);
                } else if (data.activo && badge) {
                    badge.remove();
                }

                window.showToast('success', data.mensaje);
                return actualizarElementoSidebar('modulo', item.dataset.id);
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    function toggleSubmodulo(item) {
        const url = item.dataset.toggleUrl;

        return peticion(url, 'PATCH')
            .then((data) => {
                item.dataset.activo = data.activo ? '1' : '0';
                item.classList.toggle('is-inactive', !data.activo);
                const nodoSubmodulo = item.closest('.tree-child-node');

                sincronizarCarpeta(item, Boolean(nodoSubmodulo?.classList.contains('expanded') && data.activo));

                let badge = item.querySelector('.tree-badge');

                if (!data.activo && !badge) {
                    badge = document.createElement('span');
                    badge.className = 'tree-badge';
                    badge.textContent = 'Inactivo';
                    item.querySelector('.tree-name') ?.insertAdjacentElement('afterend', badge);
                } else if (data.activo && badge) {
                    badge.remove();
                }

                window.showToast('success', data.mensaje);
                return actualizarElementoSidebar('submodulo', item.dataset.id);
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    function toggleAccion(item) {
        const url = item.dataset.toggleUrl;

        return peticion(url, 'PATCH')
            .then((data) => {
                item.dataset.activo = data.activo ? '1' : '0';
                item.classList.toggle('is-inactive', !data.activo);

                let badge =item.querySelector('.tree-badge');

                if (!data.activo && !badge) {
                    badge = document.createElement('span');
                    badge.className = 'tree-badge';
                    badge.textContent = 'Inactivo';
                    item.querySelector('.tree-name') ?.insertAdjacentElement('afterend', badge );
                } else if (data.activo && badge) {
                    badge.remove();
                }
                window.showToast('success', data.mensaje);
                return actualizarSidebar();
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    function moverModulo(item, direccion) {
        const url = item.dataset.reorderUrl;

        return peticion(url, 'PATCH', JSON.stringify({ direccion, }))
            .then((data) => {
                window.showToast('success', data.mensaje);
                return actualizarTreeview();
            })
            .then(() => {
                return actualizarSidebar();
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    function moverSubmodulo(item, direccion) {
        const url = item.dataset.reorderUrl;

        return peticion(url, 'PATCH', JSON.stringify({ direccion, })
        )
            .then((data) => {
                window.showToast('success', data.mensaje);
                return actualizarTreeview();
            })
            .then(() => {
                return actualizarSidebar();
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    function moverAccion(item, direccion) {
        const url = item.dataset.reorderUrl;

        return peticion(url, 'PATCH', JSON.stringify({ direccion, })
            )
            .then((data) => {
                window.showToast('success', data.mensaje);

                return actualizarTreeview();
            })
            .then(() => {
                return actualizarSidebar();
            })
            .catch((error) => {
                window.showToast('error', error.message);
            });
    }

    const modalNuevoModuloEl = document.getElementById('modalNuevoModulo');
    const modalEditarEl = document.getElementById('modalEditarModulo');
    const modalEliminarEl = document.getElementById('modalEliminarModulo');
    const modalNuevoSubmoduloEl = document.getElementById('modalNuevoSubmodulo');
    const modalEditarSubmoduloEl = document.getElementById('modalEditarSubmodulo');
    const modalEliminarSubmoduloEl = document.getElementById('modalEliminarSubmodulo');
    const modalNuevoAccionEl = document.getElementById('modalNuevaAccion');
    const modalEditarAccionEl = document.getElementById('modalEditarAccion');
    const modalEliminarAccionEl = document.getElementById('modalEliminarAccion');

    const modalNuevoModulo = modalNuevoModuloEl ? new bootstrap.Modal(modalNuevoModuloEl) : null;
    const modalEditar = modalEditarEl ? new bootstrap.Modal(modalEditarEl) : null;
    const modalEliminar = modalEliminarEl ? new bootstrap.Modal(modalEliminarEl) : null;
    const modalNuevoSubmodulo = modalNuevoSubmoduloEl ? new bootstrap.Modal(modalNuevoSubmoduloEl) : null;
    const modalEditarSubmodulo = modalEditarSubmoduloEl ? new bootstrap.Modal(modalEditarSubmoduloEl) : null;
    const modalEliminarSubmodulo = modalEliminarSubmoduloEl ? new bootstrap.Modal(modalEliminarSubmoduloEl): null;
    const modalNuevoAccion = modalNuevoAccionEl ? new bootstrap.Modal(modalNuevoAccionEl) : null;
    const modalEditarAccion = modalEditarAccionEl ? new bootstrap.Modal(modalEditarAccionEl) : null;
    const modalEliminarAccion = modalEliminarAccionEl ? new bootstrap.Modal(modalEliminarAccionEl) : null;

    function abrirModalEditar(item) {
        document.getElementById('editar_id').value = item.dataset.id;
        document.getElementById('editar_nombre').value = item.dataset.nombre;
        document.getElementById('editar_slug').value = item.dataset.slug || '';
        document.getElementById('editar_descripcion').value = item.dataset.descripcion || '';
        document.getElementById('editar_icono').value = item.dataset.icono || '';
        document.getElementById('formEditarModulo').setAttribute('action', item.dataset.editUrl);
        modalEditar?.show();
    }

    function abrirModalEliminar(item) {
        document.getElementById('eliminar_nombre').textContent = item.dataset.nombre;
        document.getElementById('formEliminarModulo').setAttribute('action', item.dataset.deleteUrl);
        modalEliminar?.show();
    }

    function abrirModalNuevoSubmodulo(item) {
        const moduloId = item.dataset.id;
        const moduloNombre = item.dataset.nombre;
        const form = document.getElementById('formNuevoSubmodulo');

        if (form) {
            form.setAttribute('action', '/modulos/' + moduloId + '/submodulos');
        }
        const moduloIdInput = document.getElementById('submodulo_modulo_id');
        const moduloNombreInput = document.getElementById('submodulo_modulo_nombre');

        if (moduloIdInput) {
            moduloIdInput.value = moduloId;
        }

        if (moduloNombreInput) {
            moduloNombreInput.value = moduloNombre;
        }
        document.getElementById('submodulo_nombre').value = '';
        document.getElementById('submodulo_slug').value = '';
        document.getElementById('submodulo_ruta').value = '';
        document.getElementById('submodulo_descripcion').value = '';
        document.getElementById('submodulo_icono').value = '';
        modalNuevoSubmodulo?.show();
    }

    function abrirModalEditarSubmodulo(item) {
        document.getElementById('editar_submodulo_id').value = item.dataset.id;
        document.getElementById('editar_submodulo_modulo_id').value = item.dataset.moduloId;
        document.getElementById('editar_submodulo_modulo_nombre').value = item.dataset.moduloNombre || '';
        document.getElementById('editar_submodulo_nombre').value = item.dataset.nombre || '';
        document.getElementById('editar_submodulo_slug').value = item.dataset.slug || '';
        document.getElementById('editar_submodulo_ruta').value = item.dataset.ruta || '';
        document.getElementById('editar_submodulo_descripcion').value = item.dataset.descripcion || '';
        document.getElementById('editar_submodulo_icono').value = item.dataset.icono || '';
        document.getElementById('formEditarSubmodulo').setAttribute('action',item.dataset.editUrl);
        modalEditarSubmodulo?.show();
    }

    function abrirModalEliminarSubmodulo(item) {
        document.getElementById('eliminar_submodulo_nombre').textContent = item.dataset.nombre;
        document.getElementById('formEliminarSubmodulo').setAttribute('action', item.dataset.deleteUrl);
        modalEliminarSubmodulo?.show();
    }

    function abrirModalNuevaAccion(item) {
        const submoduloId = item.dataset.id;
        const submoduloNombre = item.dataset.nombre;
        const form = document.getElementById('formNuevaAccion');

        if (form) {
            form.setAttribute('action', '/submodulos/' + submoduloId + '/acciones');
        }
        const submoduloIdInput = document.getElementById('accion_submodulo_id');
        const submoduloNombreInput = document.getElementById('accion_submodulo_nombre');

        if (submoduloIdInput) {
            submoduloIdInput.value = submoduloId;
        }

        if (submoduloNombreInput) {
            submoduloNombreInput.value = submoduloNombre;
        }
        const nombreInput = document.getElementById('accion_nombre');
        const slugInput = document.getElementById('accion_slug');
        const descripcionInput = document.getElementById('accion_descripcion');
        const iconoInput = document.getElementById('accion_icono');

        if (nombreInput) {
            nombreInput.value = '';
        }

        if (slugInput) {
            slugInput.value = '';
        }

        if (descripcionInput) {
            descripcionInput.value = '';
        }

        if (iconoInput) {
            iconoInput.value = '';
        }

        modalNuevoAccion?.show();
    }

    function abrirModalEditarAccion(item) {
        const idInput = document.getElementById('editar_accion_id');

        if (idInput) {
            idInput.value = item.dataset.id;
        }
        const submoduloIdInput = document.getElementById('editar_accion_submodulo_id');

        if (submoduloIdInput) {
            submoduloIdInput.value = item.dataset.submoduloId || '';
        }
        const submoduloNombreInput = document.getElementById('editar_accion_submodulo_nombre');

        if (submoduloNombreInput) {
            submoduloNombreInput.value = item.dataset.submoduloNombre || '';
        }
        const nombreInput = document.getElementById('editar_accion_nombre');

        if (nombreInput) {
            nombreInput.value = item.dataset.nombre || '';
        }
        const slugInput = document.getElementById('editar_accion_slug');

        if (slugInput) {
            slugInput.value = item.dataset.slug || '';
        }
        const descripcionInput = document.getElementById('editar_accion_descripcion');

        if (descripcionInput) {
            descripcionInput.value = item.dataset.descripcion || '';
        }
        const iconoInput = document.getElementById('editar_accion_icono');

        if (iconoInput) {
            iconoInput.value = item.dataset.icono || '';
        }
        const form = document.getElementById('formEditarAccion');

        if (form) {
            form.setAttribute('action', item.dataset.editUrl);
        }
        modalEditarAccion?.show();
    }

    function abrirModalEliminarAccion(item) {
        const nombreInput = document.getElementById('eliminar_accion_nombre');

        if (nombreInput) {
            nombreInput.textContent = item.dataset.nombre;
        }
        const form = document.getElementById('formEliminarAccion');

        if (form) {
            form.setAttribute('action', item.dataset.deleteUrl);
        }
        modalEliminarAccion?.show();
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.hide();
        }
    }

    // Actualiza los datos visibles del elemento editado sin reemplazar todo el treeview.
    function actualizarElementoTreeview(tipo, id, form) {
        const esModulo = tipo === 'modulo';
        const selector = esModulo
            ? '.modulo-tree-item[data-id="' + CSS.escape(String(id)) + '"]'
            : '.submodulo-tree-item[data-id="' + CSS.escape(String(id)) + '"]';
        const item = document.querySelector(selector);

        if (!item) {
            return;
        }

        const prefijo = esModulo ? 'editar_' : 'editar_submodulo_';
        const nombre = document.getElementById(prefijo + 'nombre')?.value.trim() || '';
        const slug = document.getElementById(prefijo + 'slug')?.value.trim() || '';
        const descripcion = document.getElementById(prefijo + 'descripcion')?.value.trim() || '';
        const icono = document.getElementById(prefijo + 'icono')?.value.trim() || '';

        item.dataset.nombre = nombre;
        item.dataset.slug = slug;
        item.dataset.descripcion = descripcion;
        item.dataset.icono = icono;

        if (!esModulo) {
            item.dataset.ruta = document.getElementById(prefijo + 'ruta')?.value.trim() || '';
        }

        const nombreElemento = item.querySelector(':scope > .tree-name');
        if (nombreElemento) {
            nombreElemento.textContent = nombre;
        }

        const iconoElemento = item.querySelector(':scope > .tree-icon');
        if (iconoElemento) {
            iconoElemento.innerHTML = icono || (esModulo
                ? '<i class="fa-solid fa-layer-group"></i>'
                : '<i class="fa-solid fa-circle-dot"></i>');
        }

        const botonMenu = item.querySelector(':scope > .tree-more-btn');
        if (botonMenu) {
            botonMenu.setAttribute('aria-label', 'Más acciones para ' + nombre);
        }

        if (esModulo) {
            item.querySelectorAll('.submodulo-tree-item').forEach((submodulo) => {
                submodulo.dataset.moduloNombre = nombre;
            });
        }
    }

    function enviarFormulario(form, modal, mensajePorDefecto) {
        if (form.matches(FORMULARIOS_SELECTOR)) {
            if (!validarFormulario(form)) {
                window.showToast('warning', 'Completa correctamente los campos marcados.');

                return Promise.reject(
                    new Error('Formulario inválido.')
                );
            }

            if (esFormularioEdicion(form) && !hayCambios(form)) {
                window.showToast('info', 'No hubo cambios para actualizar.');

                return Promise.reject(
                    new Error('Sin cambios.')
                );
            }
        }
        const botonSubmit = form.querySelector('button[type="submit"]');
        const textoOriginal = botonSubmit?.innerHTML;
        const esEdicionModulo = form.id === 'formEditarModulo';
        const esEdicionSubmodulo = form.id === 'formEditarSubmodulo';
        const moduloIdEditado = esEdicionModulo
            ? document.getElementById('editar_id')?.value
            : null;
        const submoduloIdEditado = esEdicionSubmodulo
            ? document.getElementById('editar_submodulo_id')?.value
            : null;

        if (botonSubmit) {
            botonSubmit.disabled = true;
            botonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>' + 'Guardando...';
        }
        const formData = new FormData(form);

        return peticion(form.action, 'POST', formData)
            .then((data) => {
                // En ediciones, modifica el nodo existente para evitar el parpadeo
                // que provocaba reemplazar el treeview completo.
                if (esEdicionModulo && moduloIdEditado) {
                    actualizarElementoTreeview('modulo', moduloIdEditado, form);
                } else if (esEdicionSubmodulo && submoduloIdEditado) {
                    actualizarElementoTreeview('submodulo', submoduloIdEditado, form);
                }

                cerrarModal(modal);
                window.showToast('success', data.mensaje || mensajePorDefecto);

                if (esEdicionModulo || esEdicionSubmodulo) {
                    return Promise.resolve();
                }

                return actualizarTreeview();
            })
            .then(() => {
                // Las ediciones actualizan solo el elemento afectado del sidebar,
                // igual que activar/desactivar, para evitar el pantallazo del menú completo.
                if (esEdicionModulo && moduloIdEditado) {
                    return actualizarElementoSidebar('modulo', moduloIdEditado, true);
                }

                if (esEdicionSubmodulo && submoduloIdEditado) {
                    return actualizarElementoSidebar('submodulo', submoduloIdEditado, true);
                }

                return actualizarSidebar();
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

    function inicializarValidacionFormularios() {
        document
            .querySelectorAll(FORMULARIOS_SELECTOR)
            .forEach((form) => {
                camposDelFormulario(form).forEach((campo) => {
                    campo.required = true;
                    campo.addEventListener('input', function () {
                        validarCampo(campo);
                    });
                });
            });

        document
            .querySelectorAll('.modal')
            .forEach((modal) => {
                modal.addEventListener('show.bs.modal', function () {
                    modal
                        .querySelectorAll(FORMULARIOS_SELECTOR)
                        .forEach((form) => {
                            camposDelFormulario(form)
                                .forEach(limpiarValidacionCampo);

                            if (esFormularioEdicion(form)) {
                                instantaneasFormulario.set(
                                    form,
                                    valoresDelFormulario(form)
                                );
                            }
                        });
                });
            });
    }

    function inicializarFormularios() {
        inicializarValidacionFormularios();

        const formNuevoModulo = document.getElementById('formNuevoModulo');
        const formEditarModulo = document.getElementById('formEditarModulo');
        const formEliminarModulo = document.getElementById('formEliminarModulo');
        const formNuevoSubmodulo = document.getElementById('formNuevoSubmodulo');
        const formEditarSubmodulo = document.getElementById('formEditarSubmodulo');
        const formEliminarSubmodulo = document.getElementById('formEliminarSubmodulo');
        const formNuevaAccion = document.getElementById('formNuevaAccion');
        const formEditarAccion = document.getElementById('formEditarAccion');
        const formEliminarAccion = document.getElementById('formEliminarAccion');

        if (formNuevoModulo) {
            formNuevoModulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();

                    enviarFormulario(formNuevoModulo, modalNuevoModulo, 'Módulo creado correctamente.')
                        .then(() => {
                            formNuevoModulo.reset();
                        })
                        .catch(() => { });
                }
            );
        }

        if (formEditarModulo) {
            formEditarModulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEditarModulo, modalEditar, 'Módulo actualizado correctamente.').catch(() => { });
                }
            );
        }

        if (formEliminarModulo) {
            formEliminarModulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEliminarModulo, modalEliminar, 'Módulo eliminado correctamente.').catch(() => { });
                }
            );
        }

        if (formNuevoSubmodulo) {
            formNuevoSubmodulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formNuevoSubmodulo, modalNuevoSubmodulo, 'Submódulo creado correctamente.')
                        .then(() => {
                            formNuevoSubmodulo.reset();
                        })
                        .catch(() => { });
                }
            );
        }

        if (formEditarSubmodulo) {
            formEditarSubmodulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEditarSubmodulo, modalEditarSubmodulo, 'Submódulo actualizado correctamente.').catch(() => { });
                }
            );
        }

        if (formEliminarSubmodulo) {
            formEliminarSubmodulo.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEliminarSubmodulo, modalEliminarSubmodulo, 'Submódulo eliminado correctamente.').catch(() => { });
                }
            );
        }

        if (formNuevaAccion) {
            formNuevaAccion.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formNuevaAccion, modalNuevoAccion, 'Acción creada correctamente.')
                        .then(() => {
                            formNuevaAccion.reset();
                        })
                        .catch(() => { });
                }
            );
        }

        if (formEditarAccion) {
            formEditarAccion.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEditarAccion, modalEditarAccion, 'Acción actualizada correctamente.').catch(() => { });
                }
            );
        }

        if (formEliminarAccion) {
            formEliminarAccion.addEventListener('submit',
                function (event) {
                    event.preventDefault();
                    enviarFormulario(formEliminarAccion, modalEliminarAccion, 'Acción eliminada correctamente.'
                    ).catch(() => { });
                }
            );
        }
    }

    const contextMenu = document.getElementById('moduloContextMenu');
    const submoduloContextMenu = document.getElementById('submoduloContextMenu');
    const accionContextMenu = document.getElementById('accionContextMenu');
    const toggleLabel = contextMenu?.querySelector('[data-role="toggle-label"]');
    const submoduloToggleLabel = submoduloContextMenu?.querySelector('[data-role="toggle-label"]');
    const accionToggleLabel = accionContextMenu?.querySelector('[data-role="toggle-label"]');

    let itemActivo = null;
    let botonActivo = null;
    let submoduloActivo = null;
    let accionActiva = null;

    function cerrarMenu() {
        contextMenu?.classList.remove('show');
        submoduloContextMenu?.classList.remove('show');
        accionContextMenu?.classList.remove('show');
        botonActivo?.classList.remove('menu-open');
        botonActivo = null;
        itemActivo = null;
        submoduloActivo = null;
        accionActiva = null;
    }

    function actualizarEstadoAcciones(item) {
        const activo = item.dataset.activo === '1';
        const esPrimero = item.dataset.first === '1';
        const esUltimo = item.dataset.last === '1';

        if (toggleLabel) {
            toggleLabel.textContent = activo ? 'Desactivar' : 'Activar';
        }
        const btnToggle = contextMenu?.querySelector('[data-action="toggle"] i');

        if (btnToggle) {
            btnToggle.className = activo ? 'fa-solid fa-power-off' : 'fa-solid fa-play';
        }
        const btnSubir = contextMenu?.querySelector('[data-action="subir"]');
        const btnBajar = contextMenu?.querySelector('[data-action="bajar"]');
        btnSubir?.classList.toggle('disabled', esPrimero);
        btnBajar?.classList.toggle('disabled', esUltimo);
    }

    function actualizarEstadoAccionesSubmodulo(item) {
        const activo = item.dataset.activo === '1';
        const esPrimero = item.dataset.first === '1';
        const esUltimo = item.dataset.last === '1';

        if (submoduloToggleLabel) {
            submoduloToggleLabel.textContent = activo ? 'Desactivar' : 'Activar';
        }
        const btnToggle = submoduloContextMenu?.querySelector('[data-action="toggle"] i');

        if (btnToggle) {
            btnToggle.className = activo ? 'fa-solid fa-power-off' : 'fa-solid fa-play';
        }
        const btnSubir = submoduloContextMenu?.querySelector('[data-action="subir"]');
        const btnBajar = submoduloContextMenu?.querySelector('[data-action="bajar"]');
        btnSubir?.classList.toggle('disabled', esPrimero);
        btnBajar?.classList.toggle('disabled', esUltimo);
    }

    function actualizarEstadoAccionesAccion(item) {
        const activo = item.dataset.activo === '1';
        const esPrimero = item.dataset.first === '1';
        const esUltimo = item.dataset.last === '1';

        if (accionToggleLabel) {
            accionToggleLabel.textContent = activo ? 'Desactivar' : 'Activar';
        }
        const btnToggle = accionContextMenu?.querySelector('[data-action="toggle"] i');

        if (btnToggle) {
            btnToggle.className = activo ? 'fa-solid fa-power-off' : 'fa-solid fa-play';
        }
        const btnSubir = accionContextMenu?.querySelector('[data-action="subir"]');
        const btnBajar = accionContextMenu?.querySelector('[data-action="bajar"]');
        btnSubir?.classList.toggle('disabled', esPrimero);
        btnBajar?.classList.toggle('disabled', esUltimo);
    }

    function posicionarMenu(menu, x, y) {
        menu.classList.add('show');
        const rect = menu.getBoundingClientRect();
        const maxX = window.innerWidth - rect.width - 8;
        const maxY = window.innerHeight - rect.height - 8;

        menu.style.left = Math.min(x, Math.max(maxX, 8)) + 'px';
        menu.style.top = Math.min(y, Math.max(maxY, 8)) + 'px';
    }

    function abrirMenu(item, x, y, boton) {
        if (!contextMenu || !contextMenu.querySelector('.context-menu-item')) {
            return;
        }
        submoduloContextMenu?.classList.remove('show');
        accionContextMenu?.classList.remove('show');
        itemActivo = item;
        submoduloActivo = null;
        accionActiva = null;
        botonActivo = boton || null;
        botonActivo?.classList.add('menu-open');
        actualizarEstadoAcciones(item);
        posicionarMenu(contextMenu, x, y);
    }

    function abrirMenuSubmodulo(item, x, y) {
        if (!submoduloContextMenu || !submoduloContextMenu.querySelector('.context-menu-item')) {
            return;
        }
        contextMenu?.classList.remove('show');
        accionContextMenu?.classList.remove('show');
        botonActivo?.classList.remove('menu-open');
        itemActivo = null;
        submoduloActivo = item;
        accionActiva = null;
        botonActivo = null;
        actualizarEstadoAccionesSubmodulo(item);
        posicionarMenu(submoduloContextMenu, x, y);
    }

    function abrirMenuAccion(item, x, y, boton) {
        if (!accionContextMenu || !accionContextMenu.querySelector('.context-menu-item')) {
            return;
        }
        contextMenu?.classList.remove('show');
        submoduloContextMenu?.classList.remove('show');
        botonActivo?.classList.remove('menu-open');
        itemActivo = null;
        submoduloActivo = null;
        accionActiva = item;
        botonActivo = boton || null;
        botonActivo?.classList.add('menu-open');
        actualizarEstadoAccionesAccion(item);
        posicionarMenu(accionContextMenu, x, y);
    }

    function inicializarTreeview() {
        items = document.querySelectorAll('.modulo-tree-item');
        submoduloItems = document.querySelectorAll('.submodulo-tree-item');
        accionItems = document.querySelectorAll('.accion-tree-item');
        items.forEach((item) => {
            item.addEventListener(
                'click',
                function () {
                    seleccionar(item);
                    alternarModulo(item);
                }
            );

            item.addEventListener(
                'contextmenu',
                function (event) {
                    event.preventDefault();
                    seleccionar(item);
                    abrirMenu(item, event.clientX, event.clientY, null);
                }
            );

            const btnMas = item.querySelector('.tree-more-btn');

            btnMas?.addEventListener(
                'click',
                function (event) {
                    event.stopPropagation();
                    seleccionar(item);
                    if (item === itemActivo) {
                        cerrarMenu();
                        return;
                    }
                    const rect = btnMas.getBoundingClientRect();
                    abrirMenu(item, rect.left, rect.bottom + 4, btnMas);
                }
            );
        });

        submoduloItems.forEach((item) => {
            item.addEventListener(
                'click',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    seleccionar(item);
                    alternarSubmodulo(item);
                }
            );

            item.addEventListener(
                'contextmenu',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    seleccionar(item);
                    abrirMenuSubmodulo(item, event.clientX, event.clientY);
                }
            );

            const btnMas = item.querySelector('.submodulo-more-btn');

            btnMas?.addEventListener(
                'click',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    seleccionar(item);

                    if (item === submoduloActivo) {
                        cerrarMenu();
                        return;
                    }
                    const rect = btnMas.getBoundingClientRect();
                    abrirMenuSubmodulo(item, rect.left, rect.bottom + 4);
                }
            );
        });

        accionItems.forEach((item) => {
            item.addEventListener(
                'click',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    seleccionar(item);
                }
            );

            item.addEventListener(
                'contextmenu',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();
                    seleccionar(item);
                    abrirMenuAccion(item, event.clientX, event.clientY, null);
                }
            );

            const btnMas = item.querySelector('.accion-more-btn');
            btnMas?.addEventListener(
                'click',
                function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    seleccionar(item);

                    if (item === accionActiva) {
                        cerrarMenu();
                        return;
                    }

                    const rect = btnMas.getBoundingClientRect();
                    abrirMenuAccion(item, rect.left, rect.bottom + 4,btnMas);
                }
            );
        });
    }

    contextMenu?.addEventListener(
        'click',
        function (event) {
            const opcion =
                event.target.closest(
                    '.context-menu-item'
                );

            if (!opcion || opcion.classList.contains('disabled') || !itemActivo) {
                return;
            }
            const item = itemActivo;
            const accion = opcion.dataset.action;

            cerrarMenu();

            switch (accion) {
                case 'toggle':
                    toggleModulo(item);
                    break;

                case 'editar':
                    abrirModalEditar(item);
                    break;

                case 'nuevo-submodulo':
                    abrirModalNuevoSubmodulo(
                        item
                    );
                    break;

                case 'eliminar':
                    abrirModalEliminar(item);
                    break;

                case 'subir':
                    moverModulo(
                        item,
                        'arriba'
                    );
                    break;

                case 'bajar':
                    moverModulo(
                        item,
                        'abajo'
                    );
                    break;
            }
        }
    );

    submoduloContextMenu?.addEventListener(
        'click',
        function (event) {
            const opcion = event.target.closest('.context-menu-item');

            if (!opcion || opcion.classList.contains('disabled') || !submoduloActivo) {
                return;
            }
            const item = submoduloActivo;
            const accion = opcion.dataset.action;

            cerrarMenu();

            switch (accion) {
                case 'toggle':
                    toggleSubmodulo(item);
                    break;

                case 'editar':
                    abrirModalEditarSubmodulo(
                        item
                    );
                    break;

                case 'nuevo-accion':
                    abrirModalNuevaAccion(
                        item
                    );
                    break;

                case 'eliminar':
                    abrirModalEliminarSubmodulo(
                        item
                    );
                    break;

                case 'subir':
                    moverSubmodulo(
                        item,
                        'arriba'
                    );
                    break;

                case 'bajar':
                    moverSubmodulo(
                        item,
                        'abajo'
                    );
                    break;
            }
        }
    );

    accionContextMenu?.addEventListener(
        'click',
        function (event) {
            const opcion = event.target.closest('.context-menu-item');

            if (!opcion || opcion.classList.contains('disabled') || !accionActiva) {
                return;
            }
            const item = accionActiva;
            const accion = opcion.dataset.action;

            cerrarMenu();

            switch (accion) {
                case 'toggle':
                    toggleAccion(item);
                    break;

                case 'editar':
                    abrirModalEditarAccion(
                        item
                    );
                    break;

                case 'eliminar':
                    abrirModalEliminarAccion(
                        item
                    );
                    break;

                case 'subir':
                    moverAccion(
                        item,
                        'arriba'
                    );
                    break;

                case 'bajar':
                    moverAccion(
                        item,
                        'abajo'
                    );
                    break;
            }
        }
    );

    document.addEventListener(
        'click',
        function (event) {
            if (contextMenu && !contextMenu.contains(event.target) && submoduloContextMenu && !submoduloContextMenu.contains(event.target) && accionContextMenu && !accionContextMenu.contains(
                    event.target
                )
            ) {cerrarMenu();}
        }
    );

    document.addEventListener('scroll', cerrarMenu,true);
    window.addEventListener('resize', cerrarMenu);

    document.addEventListener(
        'keydown',
        function (event) {
            if (event.key === 'Escape') {
                cerrarMenu();
            }
        }
    );
    inicializarFormularios();
    inicializarTreeview();
});
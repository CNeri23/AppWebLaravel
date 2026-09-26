document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function peticion(url, method) {
        return fetch(url, {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
        }).then(async (response) => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                throw new Error(data.mensaje || 'Ocurrió un error al procesar la solicitud.');
            }

            return data;
        });
    }

    const items = document.querySelectorAll('.modulo-tree-item');

    function seleccionar(item) {
        items.forEach((el) => el.classList.remove('selected'));
        item.classList.add('selected');
    }

    items.forEach((item) => {
        item.addEventListener('click', function () {
            seleccionar(item);
        });
    });

    function toggleModulo(item) {
        const url = item.dataset.toggleUrl;

        return peticion(url, 'PATCH').then((data) => {
            item.dataset.activo = data.activo ? '1' : '0';
            item.classList.toggle('is-inactive', !data.activo);

            const folderIcon = item.querySelector('.tree-folder i');
            if (folderIcon) {
                folderIcon.classList.toggle('fa-folder-open', data.activo);
                folderIcon.classList.toggle('fa-folder', !data.activo);
            }

            let badge = item.querySelector('.tree-badge');
            if (!data.activo && !badge) {
                badge = document.createElement('span');
                badge.className = 'tree-badge';
                badge.textContent = 'Inactivo';
                item.querySelector('.tree-name').insertAdjacentElement('afterend', badge);
            } else if (data.activo && badge) {
                badge.remove();
            }

            window.showToast('success', data.mensaje);
        }).catch((error) => {
            window.showToast('error', error.message);
        });
    }

    function moverModulo(item, direccion) {
        const url = item.dataset.reorderUrl;

        return fetch(url, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ direccion }),
        }).then(async (response) => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.success) {
                throw new Error(data.mensaje || 'No fue posible cambiar el orden del módulo.');
            }

            window.showToast('success', data.mensaje);
            setTimeout(() => window.location.reload(), 400);
        }).catch((error) => {
            window.showToast('error', error.message);
        });
    }


    const modalEditarEl = document.getElementById('modalEditarModulo');
    const modalEliminarEl = document.getElementById('modalEliminarModulo');
    const modalEditar = modalEditarEl ? new bootstrap.Modal(modalEditarEl) : null;
    const modalEliminar = modalEliminarEl ? new bootstrap.Modal(modalEliminarEl) : null;

    function abrirModalEditar(item) {
        document.getElementById('editar_id').value = item.dataset.id;
        document.getElementById('editar_nombre').value = item.dataset.nombre;
        document.getElementById('editar_slug').value = item.dataset.slug || '';
        document.getElementById('editar_descripcion').value = item.dataset.descripcion || '';
        document.getElementById('editar_icono').value = item.dataset.icono || '';
        document.getElementById('editar_orden').value = item.dataset.orden;
        document.getElementById('formEditarModulo').setAttribute('action', item.dataset.editUrl);

        modalEditar?.show();
    }

    function abrirModalEliminar(item) {
        document.getElementById('eliminar_nombre').textContent = item.dataset.nombre;
        document.getElementById('formEliminarModulo').setAttribute('action', item.dataset.deleteUrl);

        modalEliminar?.show();
    }

    const contextMenu = document.getElementById('moduloContextMenu');
    const toggleLabel = contextMenu?.querySelector('[data-role="toggle-label"]');
    let itemActivo = null;
    let botonActivo = null;

    function cerrarMenu() {
        contextMenu?.classList.remove('show');
        botonActivo?.classList.remove('menu-open');
        botonActivo = null;
        itemActivo = null;
    }

    function actualizarEstadoAcciones(item) {
        const activo = item.dataset.activo === '1';
        const esPrimero = item.dataset.first === '1';
        const esUltimo = item.dataset.last === '1';

        if (toggleLabel) {
            toggleLabel.textContent = activo ? 'Desactivar' : 'Activar';
        }

        const btnToggle = contextMenu.querySelector('[data-action="toggle"] i');
        if (btnToggle) {
            btnToggle.className = activo ? 'fa-solid fa-power-off' : 'fa-solid fa-play';
        }

        const btnSubir = contextMenu.querySelector('[data-action="subir"]');
        const btnBajar = contextMenu.querySelector('[data-action="bajar"]');

        btnSubir?.classList.toggle('disabled', esPrimero);
        btnBajar?.classList.toggle('disabled', esUltimo);
    }

    function abrirMenu(item, x, y, boton) {
        if (!contextMenu) {
            return;
        }
        itemActivo = item;
        botonActivo = boton || null;
        botonActivo?.classList.add('menu-open');

        actualizarEstadoAcciones(item);
        contextMenu.classList.add('show');

        const rect = contextMenu.getBoundingClientRect();
        const maxX = window.innerWidth - rect.width - 8;
        const maxY = window.innerHeight - rect.height - 8;

        contextMenu.style.left =
            Math.min(x, Math.max(maxX, 8)) + 'px';

        contextMenu.style.top =
            Math.min(y, Math.max(maxY, 8)) + 'px';
    }

    items.forEach((item) => {
        item.addEventListener('contextmenu', function (event) {
            event.preventDefault();
            seleccionar(item);
            abrirMenu(item, event.clientX, event.clientY, null);
        });

        const btnMas = item.querySelector('.tree-more-btn');
        btnMas?.addEventListener('click', function (event) {
            event.stopPropagation();
            seleccionar(item);

            if (item === itemActivo) {
                cerrarMenu();
                return;
            }

            const rect = btnMas.getBoundingClientRect();
            abrirMenu(item, rect.left, rect.bottom + 4, btnMas);
        });
    });

    contextMenu?.addEventListener('click', function (event) {
        const opcion = event.target.closest('.context-menu-item');

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
            case 'eliminar':
                abrirModalEliminar(item);
                break;
            case 'subir':
                moverModulo(item, 'arriba');
                break;
            case 'bajar':
                moverModulo(item, 'abajo');
                break;
        }
    });

    document.addEventListener('click', function (event) {
        if (contextMenu && !contextMenu.contains(event.target)) {
            cerrarMenu();
        }
    });

    document.addEventListener('scroll', cerrarMenu, true);
    window.addEventListener('resize', cerrarMenu);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            cerrarMenu();
        }
    });

});
document.addEventListener('DOMContentLoaded', function () {
    /* =================================================================
       TEMA CLARO / OSCURO (toggle)
       ================================================================= */

    const html = document.documentElement;
    const themeSwitch = document.getElementById('themeSwitch');

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function leerTema() {
        let guardado = localStorage.getItem('admin-theme');

        // Migración: el modo "auto" ya no existe
        if (guardado === 'auto') {
            guardado = getSystemTheme();
            localStorage.setItem('admin-theme', guardado);
        }

        return guardado === 'dark' ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        html.setAttribute('data-bs-theme', theme);

        if (themeSwitch) {
            themeSwitch.setAttribute(
                'aria-checked',
                theme === 'dark' ? 'true' : 'false'
            );

            themeSwitch.title =
                theme === 'dark'
                    ? 'Cambiar a modo claro'
                    : 'Cambiar a modo oscuro';
        }
    }

    applyTheme(leerTema());

    function guardarYAplicar(tema) {
        localStorage.setItem('admin-theme', tema);
        applyTheme(tema);
    }

    function cambiarTema(siguiente) {
        const reducirMovimiento = window
            .matchMedia('(prefers-reduced-motion: reduce)')
            .matches;

        // Con View Transitions: el nuevo tema se expande como un círculo
        // desde el toggle. Si no hay soporte, se hace un fundido de colores.
        if (!document.startViewTransition || reducirMovimiento) {
            html.classList.add('theme-switching');
            guardarYAplicar(siguiente);

            setTimeout(function () {
                html.classList.remove('theme-switching');
            }, 450);

            return;
        }

        const caja = themeSwitch.getBoundingClientRect();
        const x = caja.left + caja.width / 2;
        const y = caja.top + caja.height / 2;

        const radio = Math.hypot(
            Math.max(x, window.innerWidth - x),
            Math.max(y, window.innerHeight - y)
        );

        const transicion = document.startViewTransition(function () {
            guardarYAplicar(siguiente);
        });

        transicion.ready.then(function () {
            html.animate(
                {
                    clipPath: [
                        `circle(0px at ${x}px ${y}px)`,
                        `circle(${radio}px at ${x}px ${y}px)`,
                    ],
                },
                {
                    duration: 600,
                    easing: 'cubic-bezier(0.4, 0, 0.2, 1)',
                    pseudoElement: '::view-transition-new(root)',
                }
            );
        }).catch(function () { });
    }

    themeSwitch?.addEventListener('click', function () {
        const actual = html.getAttribute('data-bs-theme');

        cambiarTema(actual === 'dark' ? 'light' : 'dark');
    });

    // Mantiene sincronizadas varias pestañas abiertas
    window.addEventListener('storage', function (event) {
        if (event.key === 'admin-theme') {
            applyTheme(leerTema());
        }
    });

    const sidebar = document.getElementById('appSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

    if (!sidebar) {
        return;
    }

    function esMovil() {
        return window.matchMedia('(max-width: 991.98px)').matches;
    }

    function toggleMobileSidebar() {
        sidebar.classList.toggle('show');
        overlay?.classList.toggle('show');
    }

    overlay?.addEventListener('click', toggleMobileSidebar);

    function obtenerGruposSidebar() {
        return sidebar.querySelectorAll(
            '.sidebar-group-toggle[data-bs-target]'
        );
    }

    function obtenerSubmenu(button) {
        const target = button.getAttribute('data-bs-target');

        if (!target) {
            return null;
        }

        try {
            return document.querySelector(target);
        } catch (error) {
            return null;
        }
    }

    const DURACION_SUBMENU = 380;
    const CURVA_SUBMENU = 'cubic-bezier(0.32, 0.72, 0, 1)';

    function reducirMovimiento() {
        return window
            .matchMedia('(prefers-reduced-motion: reduce)')
            .matches;
    }

    function limpiarEstilosSubmenu(submenu) {
        submenu.style.height = '';
        submenu.style.overflow = '';
        submenu.style.transition = '';
        submenu.style.paddingTop = '';
        submenu.style.paddingBottom = '';
        submenu.style.marginTop = '';
        submenu.style.marginBottom = '';
    }

    function cerrarSubmenu(button, submenu, animar = false) {
        if (!submenu) {
            return;
        }

        clearTimeout(submenu._timer);

        button.setAttribute('aria-expanded', 'false');

        const visible = submenu.classList.contains('show');

        if (!animar || !visible || reducirMovimiento()) {
            submenu.classList.remove('show');
            submenu.classList.remove('collapsing');

            limpiarEstilosSubmenu(submenu);

            return;
        }

        // Fija la altura actual y la lleva a 0
        submenu.style.height = submenu.offsetHeight + 'px';
        submenu.style.overflow = 'hidden';

        submenu.getBoundingClientRect();

        submenu.style.transition =
            `height ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}, ` +
            `padding ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}, ` +
            `margin ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}`;

        submenu.style.height = '0px';
        submenu.style.paddingTop = '0px';
        submenu.style.paddingBottom = '0px';
        submenu.style.marginTop = '0px';
        submenu.style.marginBottom = '0px';

        submenu._timer = setTimeout(function () {
            submenu.classList.remove('show');

            limpiarEstilosSubmenu(submenu);
        }, DURACION_SUBMENU + 30);
    }

    function abrirSubmenu(button, submenu, animar = false) {
        if (!submenu) {
            return;
        }

        clearTimeout(submenu._timer);

        button.setAttribute('aria-expanded', 'true');

        const yaVisible = submenu.classList.contains('show');

        if (!animar || yaVisible && !submenu.style.height || reducirMovimiento()) {
            submenu.classList.remove('collapsing');
            submenu.classList.add('show');

            limpiarEstilosSubmenu(submenu);

            submenu.style.height = 'auto';
            submenu.style.overflow = 'visible';

            return;
        }

        // Mide la altura final y la anima desde 0
        const desdeAltura = yaVisible ? submenu.offsetHeight : 0;

        submenu.classList.add('show');
        limpiarEstilosSubmenu(submenu);

        const alto = submenu.scrollHeight;

        submenu.style.overflow = 'hidden';
        submenu.style.height = desdeAltura + 'px';

        if (!yaVisible) {
            submenu.style.paddingTop = '0px';
            submenu.style.paddingBottom = '0px';
            submenu.style.marginTop = '0px';
            submenu.style.marginBottom = '0px';
        }

        submenu.getBoundingClientRect();

        submenu.style.transition =
            `height ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}, ` +
            `padding ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}, ` +
            `margin ${DURACION_SUBMENU}ms ${CURVA_SUBMENU}`;

        submenu.style.height = alto + 'px';
        submenu.style.paddingTop = '';
        submenu.style.paddingBottom = '';
        submenu.style.marginTop = '';
        submenu.style.marginBottom = '';

        submenu._timer = setTimeout(function () {
            limpiarEstilosSubmenu(submenu);

            submenu.style.height = 'auto';
            submenu.style.overflow = 'visible';
        }, DURACION_SUBMENU + 30);
    }

    function cerrarTodosLosSubmenus() {
        const sidebarGroups = obtenerGruposSidebar();

        sidebarGroups.forEach(button => {
            const submenu = obtenerSubmenu(button);

            cerrarSubmenu(button, submenu);
        });
    }

    function inicializarGruposSidebar() {
        const sidebarGroups = obtenerGruposSidebar();

        sidebarGroups.forEach(button => {
            button.removeAttribute('data-bs-toggle');

            button.addEventListener('click', function (event) {
                event.preventDefault();

                const submenu = obtenerSubmenu(button);

                if (!submenu) {
                    return;
                }

                // Con el sidebar colapsado, primero se expande y se abre el módulo
                if (
                    sidebar.classList.contains('collapsed') &&
                    !esMovil()
                ) {
                    setCollapsed(false);
                    abrirSubmenu(button, submenu);
                    return;
                }

                const abierto = submenu.classList.contains('show');

                if (abierto) {
                    cerrarSubmenu(button, submenu, true);
                } else {
                    abrirSubmenu(button, submenu, true);
                }
            });
        });

        sidebarGroups.forEach(button => {
            const submenu = obtenerSubmenu(button);

            if (!submenu) {
                return;
            }

            if (submenu.classList.contains('show')) {
                abrirSubmenu(button, submenu);
            } else {
                cerrarSubmenu(button, submenu);
            }
        });
    }

    window.actualizarSidebar = async function () {
        const sidebarMenu = sidebar.querySelector('.sidebar-menu');

        if (!sidebarMenu) {
            return;
        }

        const modulosAbiertos = [];

        sidebar.querySelectorAll(
            '.sidebar-group-toggle[data-modulo-id]'
        ).forEach(button => {
            const submenu = obtenerSubmenu(button);

            if (
                submenu &&
                submenu.classList.contains('show')
            ) {
                modulosAbiertos.push(
                    button.dataset.moduloId
                );
            }
        });

        const estabaColapsado =
            sidebar.classList.contains('collapsed');

        try {
            const response = await fetch(
                window.location.href,
                {
                    method: 'GET',
                    headers: {
                        'Accept': 'text/html',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    cache: 'no-store',
                }
            );

            if (!response.ok) {
                throw new Error(
                    'No fue posible actualizar el menú lateral.'
                );
            }

            const html = await response.text();

            const documento =
                new DOMParser().parseFromString(
                    html,
                    'text/html'
                );

            const nuevoSidebarMenu =
                documento.querySelector('.sidebar-menu');

            if (!nuevoSidebarMenu) {
                throw new Error(
                    'No fue posible encontrar el menú lateral.'
                );
            }

            sidebarMenu.innerHTML =
                nuevoSidebarMenu.innerHTML;

            inicializarGruposSidebar();

            modulosAbiertos.forEach(moduloId => {
                const button = sidebar.querySelector(
                    `.sidebar-group-toggle[data-modulo-id="${moduloId}"]`
                );

                if (!button) {
                    return;
                }

                const submenu =
                    obtenerSubmenu(button);

                if (submenu) {
                    abrirSubmenu(button, submenu);
                }
            });

            if (estabaColapsado) {
                sidebar.classList.add('collapsed');
                document.body.classList.add(
                    'sidebar-collapsed'
                );
            }

            actualizarTitulos(estabaColapsado);

        } catch (error) {
            console.error(
                'Error al actualizar el sidebar:',
                error
            );
        }
    };

    inicializarGruposSidebar();

    // Tooltips nativos con el nombre cuando solo se ven los iconos
    function actualizarTitulos(collapsed) {
        sidebar
            .querySelectorAll(
                '.sidebar-link[data-label], .sidebar-group-toggle[data-label]'
            )
            .forEach(elemento => {
                if (collapsed) {
                    elemento.title = elemento.dataset.label;
                } else {
                    elemento.removeAttribute('title');
                }
            });
    }

    // Al expandir, vuelve a abrir el módulo de la página actual
    function abrirModuloActivo() {
        const activo = sidebar.querySelector('.sidebar-sublink.active');

        if (!activo) {
            return;
        }

        const submenu = activo.closest('.sidebar-submenu');

        if (!submenu) {
            return;
        }

        const button = sidebar.querySelector(
            `.sidebar-group-toggle[data-bs-target="#${submenu.id}"]`
        );

        if (button) {
            abrirSubmenu(button, submenu);
        }
    }

    function setCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);

        document.body.classList.toggle(
            'sidebar-collapsed',
            collapsed
        );

        sidebarToggleBtn?.setAttribute(
            'aria-expanded',
            collapsed ? 'false' : 'true'
        );

        localStorage.setItem(
            'sidebar-collapsed',
            collapsed ? '1' : '0'
        );

        actualizarTitulos(collapsed);

        if (collapsed) {
            cerrarTodosLosSubmenus();
        } else {
            abrirModuloActivo();
        }
    }

    if (window.matchMedia('(min-width: 992px)').matches) {
        const guardado =
            localStorage.getItem('sidebar-collapsed') === '1';

        setCollapsed(guardado);
    }

    sidebarToggleBtn?.addEventListener('click', function () {
        if (esMovil()) {
            toggleMobileSidebar();
            return;
        }

        const estaColapsado =
            sidebar.classList.contains('collapsed');

        setCollapsed(!estaColapsado);
    });

    let anchoAnterior = window.innerWidth;

    window.addEventListener('resize', function () {
        const anchoActual = window.innerWidth;

        if (
            anchoAnterior >= 992 &&
            anchoActual < 992
        ) {
            sidebar.classList.remove('collapsed');
            document.body.classList.remove(
                'sidebar-collapsed'
            );

            cerrarTodosLosSubmenus();
        }

        if (
            anchoAnterior < 992 &&
            anchoActual >= 992
        ) {
            const guardado =
                localStorage.getItem('sidebar-collapsed') === '1';

            setCollapsed(guardado);
        }

        anchoAnterior = anchoActual;
    });
});
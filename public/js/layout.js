document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const themeIcon = document.getElementById('themeIcon');

    function getSystemTheme() {
        return window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light';
    }

    function applyTheme(theme) {
        const finalTheme = theme === 'auto' ? getSystemTheme() : theme;
        html.setAttribute('data-bs-theme', finalTheme);

        if (themeIcon) {
            if (theme === 'auto') {
                themeIcon.className = 'fa-solid fa-circle-half-stroke';
            } else if (finalTheme === 'dark') {
                themeIcon.className = 'fa-solid fa-moon';
            } else {
                themeIcon.className = 'fa-solid fa-sun';
            }
        }
    }

    const savedTheme = localStorage.getItem('admin-theme') || 'light';

    applyTheme(savedTheme);

    document.querySelectorAll('.theme-option').forEach(button => {
        button.addEventListener('click', function () {
            const theme = this.dataset.theme;

            localStorage.setItem('admin-theme', theme);
            applyTheme(theme);
        });
    });

    window
        .matchMedia('(prefers-color-scheme: dark)')
        .addEventListener('change', function () {
            const saved = localStorage.getItem('admin-theme');

            if (saved === 'auto') {
                applyTheme('auto');
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

    function cerrarSubmenu(button, submenu) {
        if (!submenu) {
            return;
        }

        submenu.classList.remove('show');
        submenu.classList.remove('collapsing');

        submenu.style.height = '';
        submenu.style.overflow = '';

        button.setAttribute('aria-expanded', 'false');
    }

    function abrirSubmenu(button, submenu) {
        if (!submenu) {
            return;
        }

        submenu.classList.remove('collapsing');
        submenu.classList.add('show');

        submenu.style.height = 'auto';
        submenu.style.overflow = 'visible';

        button.setAttribute('aria-expanded', 'true');
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

                if (sidebar.classList.contains('collapsed')) {
                    return;
                }

                const submenu = obtenerSubmenu(button);

                if (!submenu) {
                    return;
                }

                const abierto = submenu.classList.contains('show');

                if (abierto) {
                    cerrarSubmenu(button, submenu);
                } else {
                    abrirSubmenu(button, submenu);
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

        } catch (error) {
            console.error(
                'Error al actualizar el sidebar:',
                error
            );
        }
    };

    inicializarGruposSidebar();

    function setCollapsed(collapsed) {
        sidebar.classList.toggle('collapsed', collapsed);

        document.body.classList.toggle(
            'sidebar-collapsed',
            collapsed
        );

        localStorage.setItem(
            'sidebar-collapsed',
            collapsed ? '1' : '0'
        );

        if (collapsed) {
            cerrarTodosLosSubmenus();
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
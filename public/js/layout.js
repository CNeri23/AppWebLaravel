(function aplicarTemaInicial() {
    try {
        var raiz = document.documentElement;
        var modo = raiz.dataset.themeMode || 'light';
        var tema = modo === 'dark' ? 'dark' : 'light';

        raiz.setAttribute('data-bs-theme', tema);

        raiz.setAttribute(
            'data-theme-style',
            tema === 'dark'
                ? (raiz.dataset.darkThemeStyle || 'graphite')
                : (raiz.dataset.lightThemeStyle || 'white')
        );
    } catch (e) { }
})();

document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;

    const loginThemeToggle = document.getElementById('loginThemeToggle');
    const esPantallaLogin = Boolean(loginThemeToggle);

    const themeSwitch =
        document.getElementById('themeSwitch') ||
        loginThemeToggle;

    let themeMode = html.dataset.themeMode || 'light';
    let lightThemeStyle = html.dataset.lightThemeStyle || 'white';
    let darkThemeStyle = html.dataset.darkThemeStyle || 'graphite';

    function obtenerEstiloPredeterminado(theme) {
        return theme === 'dark'
            ? 'graphite'
            : 'white';
    }

    function esEstiloClaro(style) {
        return [
            'white',
            'mist',
            'sky',
        ].includes(style);
    }

    function esEstiloOscuro(style) {
        return [
            'graphite',
            'charcoal',
            'black',
        ].includes(style);
    }

    function obtenerEstiloParaTema(theme) {
        if (theme === 'dark') {
            if (esEstiloOscuro(darkThemeStyle)) {
                return darkThemeStyle;
            }

            return 'graphite';
        }

        if (esEstiloClaro(lightThemeStyle)) {
            return lightThemeStyle;
        }

        return 'white';
    }

    function applyTheme(theme) {
        const estiloClaroActual =
            html.getAttribute('data-light-theme-style');

        const estiloOscuroActual =
            html.getAttribute('data-dark-theme-style');

        if (esEstiloClaro(estiloClaroActual)) {
            lightThemeStyle = estiloClaroActual;
        }

        if (esEstiloOscuro(estiloOscuroActual)) {
            darkThemeStyle = estiloOscuroActual;
        }

        html.setAttribute('data-bs-theme', theme);

        const estiloActual =
            obtenerEstiloParaTema(theme);

        html.setAttribute(
            'data-theme-style',
            estiloActual
        );

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

        document.dispatchEvent(
            new CustomEvent('ironpulse:theme-changed', {
                detail: {
                    theme,
                    style: estiloActual,
                },
            })
        );
    }

    function applyThemeMode(mode) {
        if (mode !== 'light' && mode !== 'dark') {
            mode = 'light';
        }

        themeMode = mode;

        html.setAttribute(
            'data-theme-mode',
            themeMode
        );

        applyTheme(themeMode);
    }

    function applyThemeStyle(style) {
        if (!style) {
            applyTheme(themeMode);

            return;
        }

        if (esEstiloClaro(style)) {
            lightThemeStyle = style;

            html.setAttribute(
                'data-light-theme-style',
                lightThemeStyle
            );
        }

        if (esEstiloOscuro(style)) {
            darkThemeStyle = style;

            html.setAttribute(
                'data-dark-theme-style',
                darkThemeStyle
            );
        }

        applyTheme(themeMode);
    }

    function applyThemeStyles(lightStyle, darkStyle) {
        if (esEstiloClaro(lightStyle)) {
            lightThemeStyle = lightStyle;

            html.setAttribute(
                'data-light-theme-style',
                lightThemeStyle
            );
        }

        if (esEstiloOscuro(darkStyle)) {
            darkThemeStyle = darkStyle;

            html.setAttribute(
                'data-dark-theme-style',
                darkThemeStyle
            );
        }

        applyTheme(themeMode);
    }

    function applyAccentColor(color) {
        if (!color) {
            return;
        }

        html.setAttribute(
            'data-accent-color',
            color
        );
    }

    function applySystemName(systemName) {
        if (!systemName) {
            return;
        }

        document
            .querySelectorAll('.sidebar-brand-text')
            .forEach(elemento => {
                elemento.textContent = systemName;
            });

        const titulo = document.querySelector('title');

        if (
            titulo &&
            !titulo.dataset.customTitle
        ) {
            titulo.textContent = systemName;
        }
    }

    function applyLogo(logoPath, systemName) {
        const brandIcon =
            document.querySelector('.sidebar-brand-icon');

        if (!brandIcon) {
            return;
        }

        if (logoPath) {
            brandIcon.innerHTML = '';

            const imagen =
                document.createElement('img');

            imagen.src =
                '/storage/' + logoPath;

            imagen.alt =
                systemName || 'Logotipo';

            brandIcon.appendChild(imagen);

            return;
        }

        brandIcon.innerHTML =
            '<i class="fa-solid fa-heart-pulse"></i>';
    }

    async function guardarPreferenciasTema(preferencias) {
        const csrf =
            document.querySelector(
                'meta[name="csrf-token"]'
            )?.getAttribute('content');

        if (!csrf) {
            throw new Error(
                'No fue posible obtener el token de seguridad.'
            );
        }

        const response = await fetch(
            '/preferencias/tema',
            {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(preferencias),
            }
        );

        let data = null;

        try {
            data = await response.json();
        } catch (e) { }

        if (!response.ok || !data?.success) {
            throw new Error(
                data?.mensaje ||
                'No fue posible guardar las preferencias del tema.'
            );
        }

        return data;
    }

    const CLAVE_ACTIVIDAD = 'ironpulse-last-activity';
    let minutosSesion = 0;
    let temporizadorSesion = null;
    let ultimaActividad = Date.now();
    let ultimaEscritura = 0;
    let ultimoPing = Date.now();
    let redirigiendoSesion = false;

    function irAlLogin() {
        if (redirigiendoSesion) {
            return;
        }

        redirigiendoSesion = true;

        const base = html.dataset.loginUrl || '/login';

        window.location.href =
            base + (base.includes('?') ? '&' : '?') + 'expirada=1';
    }

    function actividadCompartida() {
        let ultima = ultimaActividad;

        try {
            const guardada = parseInt(
                localStorage.getItem(CLAVE_ACTIVIDAD),
                10
            );

            if (guardada > ultima) {
                ultima = guardada;
            }
        } catch (e) { }

        return ultima;
    }

    function revisarSesion() {
        if (!minutosSesion) {
            return;
        }

        if (Date.now() - actividadCompartida() >= minutosSesion * 60000 + 1500) {
            irAlLogin();
        }
    }

    function mantenerSesion() {
        ultimoPing = Date.now();

        fetch(window.location.href, {
            method: 'HEAD',
            credentials: 'same-origin',
            cache: 'no-store',
            redirect: 'manual',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then(function (respuesta) {
            if (respuesta.status === 401) {
                irAlLogin();
            }
        }).catch(function () { });
    }

    function registrarActividad() {
        if (!minutosSesion) {
            return;
        }

        const ahora = Date.now();

        ultimaActividad = ahora;

        if (ahora - ultimaEscritura > 5000) {
            ultimaEscritura = ahora;

            try {
                localStorage.setItem(CLAVE_ACTIVIDAD, String(ahora));
            } catch (e) { }
        }

        if (ahora - ultimoPing > (minutosSesion * 60000) / 2) {
            mantenerSesion();
        }
    }

    function configurarSesion(minutos) {
        minutosSesion = Math.max(0, parseInt(minutos, 10) || 0);

        html.dataset.sessionTimeout = String(minutosSesion);

        clearInterval(temporizadorSesion);
        temporizadorSesion = null;

        if (!minutosSesion) {
            return;
        }

        ultimaActividad = Date.now();
        ultimoPing = ultimaActividad;
        ultimaEscritura = 0;

        registrarActividad();

        temporizadorSesion = setInterval(revisarSesion, 5000);
    }

    [
        'mousemove',
        'mousedown',
        'keydown',
        'scroll',
        'wheel',
        'touchstart',
    ].forEach(function (evento) {
        window.addEventListener(evento, registrarActividad, {
            passive: true,
            capture: true,
        });
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            revisarSesion();
        }
    });

    window.addEventListener('pageshow', revisarSesion);

    function actualizarSaludo(zona) {
        const texto = document.getElementById('topbarGreetingText');

        if (!texto) {
            return;
        }

        if (zona) {
            html.dataset.timezone = zona;
        }

        let hora;

        try {
            hora = parseInt(
                new Intl.DateTimeFormat('en-US', {
                    hour: 'numeric',
                    hourCycle: 'h23',
                    timeZone: html.dataset.timezone || undefined,
                }).format(new Date()),
                10
            );
        } catch (e) {
            hora = new Date().getHours();
        }

        texto.textContent =
            hora < 12
                ? 'Buenos días'
                : hora < 19
                    ? 'Buenas tardes'
                    : 'Buenas noches';
    }

    window.aplicarConfiguracionGlobal = function (settings) {
        if (!settings) {
            return;
        }

        if (
            Object.prototype.hasOwnProperty.call(
                settings,
                'timezone'
            )
        ) {
            actualizarSaludo(settings.timezone);
        }

        if (
            Object.prototype.hasOwnProperty.call(
                settings,
                'session_timeout'
            )
        ) {
            configurarSesion(settings.session_timeout);
        }

        if (
            Object.prototype.hasOwnProperty.call(
                settings,
                'accent_color'
            )
        ) {
            applyAccentColor(
                settings.accent_color
            );
        }

        if (
            Object.prototype.hasOwnProperty.call(
                settings,
                'system_name'
            )
        ) {
            applySystemName(
                settings.system_name
            );
        }

        if (
            Object.prototype.hasOwnProperty.call(
                settings,
                'logo_path'
            )
        ) {
            applyLogo(
                settings.logo_path,
                settings.system_name ||
                html.dataset.systemName
            );
        }
    };

    if (!esEstiloClaro(lightThemeStyle)) {
        lightThemeStyle =
            obtenerEstiloPredeterminado('light');
    }

    if (!esEstiloOscuro(darkThemeStyle)) {
        darkThemeStyle =
            obtenerEstiloPredeterminado('dark');
    }

    applyThemeStyles(
        lightThemeStyle,
        darkThemeStyle
    );

    applyThemeMode(themeMode);

    applyAccentColor(html.dataset.accentColor);

    configurarSesion(html.dataset.sessionTimeout);

    actualizarSaludo();

    setInterval(actualizarSaludo, 60000);

    async function guardarYAplicar(tema) {
        const modoAnterior = themeMode;
        const estiloClaroAnterior = lightThemeStyle;
        const estiloOscuroAnterior = darkThemeStyle;

        themeMode = tema;

        applyTheme(tema);

        // En el login no hay un usuario autenticado al que guardar preferencias.
        // El cambio es solo visual; las preferencias personales se guardan dentro del sistema.
        if (esPantallaLogin) {
            return;
        }

        try {
            const respuesta =
                await guardarPreferenciasTema({
                    theme_mode: themeMode,
                    light_theme_style: lightThemeStyle,
                    dark_theme_style: darkThemeStyle,
                });

            if (respuesta.preferences) {
                themeMode =
                    respuesta.preferences.theme_mode;

                lightThemeStyle =
                    respuesta.preferences.light_theme_style;

                darkThemeStyle =
                    respuesta.preferences.dark_theme_style;

                html.setAttribute(
                    'data-theme-mode',
                    themeMode
                );

                html.setAttribute(
                    'data-light-theme-style',
                    lightThemeStyle
                );

                html.setAttribute(
                    'data-dark-theme-style',
                    darkThemeStyle
                );

                applyTheme(themeMode);
            }

            if (typeof window.mostrarAviso === 'function') {
                window.mostrarAviso(
                    respuesta.mensaje ||
                    'El tema se actualizó correctamente.',
                    'success'
                );
            }
        } catch (error) {
            themeMode = modoAnterior;
            lightThemeStyle = estiloClaroAnterior;
            darkThemeStyle = estiloOscuroAnterior;

            html.setAttribute(
                'data-theme-mode',
                themeMode
            );

            html.setAttribute(
                'data-light-theme-style',
                lightThemeStyle
            );

            html.setAttribute(
                'data-dark-theme-style',
                darkThemeStyle
            );

            applyTheme(themeMode);

            if (typeof window.mostrarAviso === 'function') {
                window.mostrarAviso(
                    error.message ||
                    'No fue posible actualizar el tema.',
                    'danger'
                );
            }
        }
    }

    function cambiarTema(siguiente) {
        const reducirMovimiento = window
            .matchMedia('(prefers-reduced-motion: reduce)')
            .matches;

        if (!document.startViewTransition || reducirMovimiento) {
            html.classList.add('theme-switching');

            guardarYAplicar(siguiente);

            setTimeout(function () {
                html.classList.remove('theme-switching');
            }, 450);

            return;
        }

        if (!themeSwitch) {
            guardarYAplicar(siguiente);

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

    window.cambiarTemaManual = cambiarTema;

    document.addEventListener('ironpulse:preferences-updated', function (event) {
        const preferencias = event.detail || {};

        if (
            preferencias.theme_mode !== 'light' &&
            preferencias.theme_mode !== 'dark'
        ) {
            return;
        }

        themeMode = preferencias.theme_mode;

        if (esEstiloClaro(preferencias.light_theme_style)) {
            lightThemeStyle = preferencias.light_theme_style;
        }

        if (esEstiloOscuro(preferencias.dark_theme_style)) {
            darkThemeStyle = preferencias.dark_theme_style;
        }

        html.setAttribute(
            'data-theme-mode',
            themeMode
        );

        html.setAttribute(
            'data-light-theme-style',
            lightThemeStyle
        );

        html.setAttribute(
            'data-dark-theme-style',
            darkThemeStyle
        );

        applyTheme(themeMode);
    });

    themeSwitch?.addEventListener('click', function () {
        const actual =
            html.getAttribute('data-bs-theme');

        cambiarTema(
            actual === 'dark'
                ? 'light'
                : 'dark'
        );
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
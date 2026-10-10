document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const opciones = document.querySelectorAll('input[name="light_theme_style"], input[name="dark_theme_style"]');

    let themeMode = html.dataset.themeMode || 'light';
    let lightThemeStyle = html.dataset.lightThemeStyle || 'white';
    let darkThemeStyle = html.dataset.darkThemeStyle || 'graphite';

    const csrf = document.querySelector('meta[name="csrf-token"]') ?.getAttribute('content');

    function esEstiloClaro(style) {
        return ['white', 'mist', 'sky',].includes(style);
    }

    function esEstiloOscuro(style) {
        return ['graphite', 'charcoal', 'black',].includes(style);
    }

    function obtenerEstiloActual() {
        return themeMode === 'dark' ? darkThemeStyle : lightThemeStyle;
    }

    function aplicarTemaActual() {
        html.setAttribute('data-theme-mode', themeMode);
        html.setAttribute('data-light-theme-style', lightThemeStyle);
        html.setAttribute('data-dark-theme-style', darkThemeStyle);
        html.setAttribute('data-theme-style', obtenerEstiloActual());
        html.setAttribute('data-bs-theme', themeMode);
    }

    function actualizarSeleccionVisual() {
        opciones.forEach(function (opcion) {
            const estiloActivo = opcion.name === 'light_theme_style' ? opcion.value === lightThemeStyle : opcion.value === darkThemeStyle;
            opcion.checked = estiloActivo;
        });
    }

    function mostrarAviso(mensaje, tipo) {
        if (typeof window.showToast === 'function') {
            window.showToast(tipo, mensaje);
            return;
        }

        if (typeof window.mostrarAviso === 'function') {
            window.mostrarAviso(mensaje, tipo === 'success' ? 'success' : 'danger');
        }
    }

    async function guardarPreferencia() {
        if (!csrf) {
            throw new Error('No fue posible obtener el token de seguridad.');
        }
        const response = await fetch('/preferencias/tema',
            {
                method: 'PUT',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    theme_mode: themeMode,
                    light_theme_style: lightThemeStyle,
                    dark_theme_style: darkThemeStyle,
                }),
            }
        );
        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data?.success) {
            throw new Error(data?.mensaje || data?.message || 'No fue posible guardar la preferencia.');
        }
        return data;
    }

    document.addEventListener('ironpulse:theme-changed',
        function (event) {
            const detalle = event.detail || {};

            if (detalle.theme !== 'light' && detalle.theme !== 'dark') {
                return;
            }
            themeMode = detalle.theme;

            const estilo = detalle.style || '';

            if (themeMode === 'light' && esEstiloClaro(estilo)) {
                lightThemeStyle = estilo;
            }

            if (themeMode === 'dark' && esEstiloOscuro(estilo)) {
                darkThemeStyle = estilo;
            }
            html.setAttribute('data-theme-mode', themeMode);
            html.setAttribute('data-theme-style', obtenerEstiloActual());
            actualizarSeleccionVisual();
        }
    );

    opciones.forEach(function (opcion) {
        opcion.addEventListener('change', async function () {
            if (!this.checked) {
                return;
            }
            const lightThemeStyleAnterior = lightThemeStyle;
            const darkThemeStyleAnterior = darkThemeStyle;

            if (this.name === 'light_theme_style') {
                lightThemeStyle = this.value;
            }

            if (this.name === 'dark_theme_style') {
                darkThemeStyle = this.value;
            }

            aplicarTemaActual();
            actualizarSeleccionVisual();

            opciones.forEach(function (elemento) {
                elemento.disabled = true;
            });

            try {
                const data = await guardarPreferencia();

                if (data.preferences) {
                    themeMode = data.preferences.theme_mode;
                    lightThemeStyle = data.preferences.light_theme_style;
                    darkThemeStyle = data.preferences.dark_theme_style;
                }
                aplicarTemaActual();
                actualizarSeleccionVisual();
                mostrarAviso(data.mensaje || 'El tema se actualizó correctamente.', 'success'
                );
            } catch (error) {
                lightThemeStyle = lightThemeStyleAnterior;
                darkThemeStyle = darkThemeStyleAnterior;
                aplicarTemaActual();
                actualizarSeleccionVisual();
                mostrarAviso(error.message || 'No fue posible actualizar el tema.','error'
                );
            } finally {
                opciones.forEach(function (elemento) {
                    elemento.disabled = false;
                });
            }
        });
    });
    aplicarTemaActual();
    actualizarSeleccionVisual();
});
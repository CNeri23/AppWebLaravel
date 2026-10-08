document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;


    const opciones = document.querySelectorAll(
        'input[name="light_theme_style"], input[name="dark_theme_style"]'
    );

    let themeMode = html.dataset.themeMode || 'light';
    let lightThemeStyle = html.dataset.lightThemeStyle || 'white';
    let darkThemeStyle = html.dataset.darkThemeStyle || 'graphite';

    const csrf =
        document.querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content');

    function aplicarEstiloInmediatamente(estilo) {
        if (
            [
                'white',
                'mist',
                'sky',
            ].includes(estilo)
        ) {
            html.setAttribute(
                'data-light-theme-style',
                estilo
            );

            if (themeMode === 'light') {
                html.setAttribute(
                    'data-theme-style',
                    estilo
                );
            }

            return;
        }

        if (
            [
                'graphite',
                'charcoal',
                'black',
            ].includes(estilo)
        ) {
            html.setAttribute(
                'data-dark-theme-style',
                estilo
            );

            if (themeMode === 'dark') {
                html.setAttribute(
                    'data-theme-style',
                    estilo
                );
            }
        }
    }

    function actualizarSeleccionVisual() {
        opciones.forEach(function (opcion) {
            if (opcion.name === 'light_theme_style') {
                opcion.checked =
                    opcion.value === lightThemeStyle;

                return;
            }

            if (opcion.name === 'dark_theme_style') {
                opcion.checked =
                    opcion.value === darkThemeStyle;
            }
        });
    }

    function actualizarAtributosTema() {
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

        html.setAttribute(
            'data-theme-style',
            themeMode === 'dark'
                ? darkThemeStyle
                : lightThemeStyle
        );

        html.setAttribute(
            'data-bs-theme',
            themeMode
        );
    }

    function notificarActualizacion() {
        document.dispatchEvent(
            new CustomEvent('ironpulse:preferences-updated', {
                detail: {
                    theme_mode: themeMode,
                    light_theme_style: lightThemeStyle,
                    dark_theme_style: darkThemeStyle,
                },
            })
        );
    }

    async function guardarPreferencia() {
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
                body: JSON.stringify({
                    theme_mode: themeMode,
                    light_theme_style: lightThemeStyle,
                    dark_theme_style: darkThemeStyle,
                }),
            }
        );

        const data =
            await response.json().catch(() => ({}));

        if (!response.ok || !data?.success) {
            throw new Error(
                data?.mensaje ||
                data?.message ||
                'No fue posible guardar la preferencia.'
            );
        }

        return data;
    }

    opciones.forEach(function (opcion) {
        opcion.addEventListener('change', async function () {
            if (!this.checked) {
                return;
            }

            const estiloClaroAnterior = lightThemeStyle;
            const estiloOscuroAnterior = darkThemeStyle;

            if (this.name === 'light_theme_style') {
                lightThemeStyle = this.value;
            }

            if (this.name === 'dark_theme_style') {
                darkThemeStyle = this.value;
            }

            aplicarEstiloInmediatamente(this.value);
            actualizarSeleccionVisual();

            opciones.forEach(function (elemento) {
                elemento.disabled = true;
            });

            try {
                const data = await guardarPreferencia();

                if (data.preferences) {
                    themeMode =
                        data.preferences.theme_mode;

                    lightThemeStyle =
                        data.preferences.light_theme_style;

                    darkThemeStyle =
                        data.preferences.dark_theme_style;
                }

                actualizarAtributosTema();
                actualizarSeleccionVisual();
                notificarActualizacion();

                window.showToast(
                    'success',
                    data.mensaje
                );
            } catch (error) {
                lightThemeStyle =
                    estiloClaroAnterior;

                darkThemeStyle =
                    estiloOscuroAnterior;

                actualizarAtributosTema();
                actualizarSeleccionVisual();

                window.showToast(
                    'error',
                    error.message ||
                    'No fue posible actualizar el tema.'
                );
            } finally {
                opciones.forEach(function (elemento) {
                    elemento.disabled = false;
                });
            }
        });
    });

    actualizarAtributosTema();
    actualizarSeleccionVisual();
});

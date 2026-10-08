document.addEventListener('DOMContentLoaded', function () {
    const html = document.documentElement;
    const opciones = document.querySelectorAll('[data-theme-style-option]');
    const currentMode = document.getElementById('preferencesCurrentMode');

    let themeMode = html.dataset.themeMode || 'light';
    let lightThemeStyle = html.dataset.lightThemeStyle || 'white';
    let darkThemeStyle = html.dataset.darkThemeStyle || 'graphite';

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function aplicarSeleccionVisual() {
        opciones.forEach(opcion => {
            const mode = opcion.dataset.themeModeOption;
            const style = opcion.dataset.themeStyleOption;
            const selected =
                mode === 'light'
                    ? style === lightThemeStyle
                    : style === darkThemeStyle;

            opcion.classList.toggle('is-selected', selected);
            opcion.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });

        if (currentMode) {
            currentMode.textContent =
                themeMode === 'dark' ? 'Oscuro' : 'Claro';
        }
    }

    async function guardarPreferencia(mode, style) {
        if (!csrf) {
            throw new Error('No fue posible obtener el token de seguridad.');
        }

        const nuevoLightStyle =
            mode === 'light'
                ? style
                : lightThemeStyle;

        const nuevoDarkStyle =
            mode === 'dark'
                ? style
                : darkThemeStyle;

        const response = await fetch('/preferencias/tema', {
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
                light_theme_style: nuevoLightStyle,
                dark_theme_style: nuevoDarkStyle,
            }),
        });

        let data = null;

        try {
            data = await response.json();
        } catch (e) { }

        if (!response.ok || !data?.success) {
            throw new Error(
                data?.mensaje ||
                'No fue posible guardar la preferencia.'
            );
        }

        return data;
    }

    opciones.forEach(opcion => {
        opcion.addEventListener('click', async function () {
            const mode = opcion.dataset.themeModeOption;
            const style = opcion.dataset.themeStyleOption;

            const previousLightStyle = lightThemeStyle;
            const previousDarkStyle = darkThemeStyle;

            if (mode === 'light') {
                lightThemeStyle = style;
            } else {
                darkThemeStyle = style;
            }

            if (themeMode === mode) {
                document.documentElement.setAttribute(
                    'data-theme-style',
                    style
                );
            }

            aplicarSeleccionVisual();

            opciones.forEach(elemento => {
                elemento.disabled = true;
            });

            try {
                const data = await guardarPreferencia(mode, style);

                if (data.preferences) {
                    themeMode = data.preferences.theme_mode;
                    lightThemeStyle = data.preferences.light_theme_style;
                    darkThemeStyle = data.preferences.dark_theme_style;

                    html.setAttribute('data-theme-mode', themeMode);
                    html.setAttribute('data-light-theme-style', lightThemeStyle);
                    html.setAttribute('data-dark-theme-style', darkThemeStyle);

                    html.setAttribute(
                        'data-theme-style',
                        themeMode === 'dark'
                            ? darkThemeStyle
                            : lightThemeStyle
                    );
                }

                aplicarSeleccionVisual();

                if (typeof window.mostrarAviso === 'function') {
                    window.mostrarAviso(
                        'La preferencia de apariencia se actualizó correctamente.',
                        'success'
                    );
                }
            } catch (error) {
                lightThemeStyle = previousLightStyle;
                darkThemeStyle = previousDarkStyle;

                html.setAttribute(
                    'data-theme-style',
                    themeMode === 'dark'
                        ? darkThemeStyle
                        : lightThemeStyle
                );

                aplicarSeleccionVisual();

                if (typeof window.mostrarAviso === 'function') {
                    window.mostrarAviso(
                        error.message || 'No fue posible actualizar la preferencia.',
                        'danger'
                    );
                }
            } finally {
                opciones.forEach(elemento => {
                    elemento.disabled = false;
                });
            }
        });
    });

    aplicarSeleccionVisual();
});

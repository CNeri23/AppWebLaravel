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

    function aplicarTema(mode) {
        themeMode = mode;

        const estiloActual =
            themeMode === 'dark'
                ? darkThemeStyle
                : lightThemeStyle;

        html.setAttribute(
            'data-theme-mode',
            themeMode
        );

        html.setAttribute(
            'data-theme-style',
            estiloActual
        );

        html.setAttribute(
            'data-bs-theme',
            themeMode
        );

        document.dispatchEvent(
            new CustomEvent('ironpulse:theme-changed', {
                detail: {
                    theme: themeMode,
                    style: estiloActual,
                },
            })
        );

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

    function actualizarSeleccionVisual() {
        opciones.forEach(function (opcion) {
            const esTemaActivo =
                themeMode === 'light'
                    ? opcion.name === 'light_theme_style'
                    : opcion.name === 'dark_theme_style';

            const estiloActivo =
                opcion.name === 'light_theme_style'
                    ? opcion.value === lightThemeStyle
                    : opcion.value === darkThemeStyle;

            opcion.checked =
                esTemaActivo && estiloActivo;
        });
    }

    function mostrarSweetAlert(mensaje, icono) {
        if (typeof window.Swal !== 'undefined') {
            window.Swal.fire({
                icon: icono,
                title: icono === 'success'
                    ? 'Preferencias actualizadas'
                    : 'No fue posible actualizar',
                text: mensaje,
                timer: icono === 'success' ? 1800 : undefined,
                showConfirmButton: icono !== 'success',
                confirmButtonText: 'Aceptar',
                timerProgressBar: icono === 'success',
                customClass: {
                    popup: 'ironpulse-swal',
                },
            });

            return;
        }

        if (typeof window.mostrarAviso === 'function') {
            window.mostrarAviso(
                mensaje,
                icono === 'success' ? 'success' : 'danger'
            );
        }
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

            const modoAnterior = themeMode;
            const estiloClaroAnterior = lightThemeStyle;
            const estiloOscuroAnterior = darkThemeStyle;

            const nuevoModo =
                this.name === 'dark_theme_style'
                    ? 'dark'
                    : 'light';

            if (nuevoModo === 'dark') {
                darkThemeStyle = this.value;
            } else {
                lightThemeStyle = this.value;
            }

            themeMode = nuevoModo;

            aplicarTema(themeMode);
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

                aplicarTema(themeMode);
                actualizarSeleccionVisual();

                mostrarSweetAlert(
                    'El tema se actualizó correctamente.',
                    'success'
                );
            } catch (error) {
                themeMode = modoAnterior;
                lightThemeStyle = estiloClaroAnterior;
                darkThemeStyle = estiloOscuroAnterior;

                aplicarTema(themeMode);
                actualizarSeleccionVisual();

                mostrarSweetAlert(
                    error.message ||
                    'No fue posible actualizar el tema.',
                    'error'
                );
            } finally {
                opciones.forEach(function (elemento) {
                    elemento.disabled = false;
                });
            }
        });
    });

    actualizarSeleccionVisual();
});

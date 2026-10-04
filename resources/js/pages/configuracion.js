document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
    const formulario = document.getElementById('formConfiguracion');
    const logoInput = document.getElementById('logo');
    const logoPreview = document.getElementById('logoPreview');
    const logoNombre = document.getElementById('logoNombre');
    const TEXTO_SIN_ARCHIVO = 'Ningún archivo seleccionado';

    function mostrarNombreLogo(texto) {
        if (logoNombre) {
            logoNombre.textContent = texto;
            logoNombre.title = texto;
        }
    }

    if (!formulario) {
        return;
    }

    const estilosClaros = [
        'white',
        'mist',
        'sky',
    ];

    const estilosOscuros = [
        'graphite',
        'charcoal',
        'black',
    ];

    let guardando = false;
    let guardadoPendiente = false;

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
            opciones.headers['Content-Type'] =
                'application/json';

            opciones.body = body;
        }

        return fetch(url, opciones).then(async (response) => {
            const data =
                await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                if (data.errors) {
                    const primerError =
                        Object.values(data.errors).flat()[0];

                    throw new Error(
                        primerError ||
                        data.mensaje ||
                        data.message ||
                        'Ocurrió un error al procesar la solicitud.'
                    );
                }

                throw new Error(
                    data.mensaje ||
                    data.message ||
                    'Ocurrió un error al procesar la solicitud.'
                );
            }

            return data;
        });
    }

    function obtenerModoActual() {
        const tema =
            document.documentElement.getAttribute(
                'data-bs-theme'
            );

        return tema === 'dark'
            ? 'dark'
            : 'light';
    }

    function obtenerDatosFormulario() {
        const datos = new FormData(formulario);

        datos.append('_method', 'PUT');

        return datos;
    }

    function aplicarTemaInmediatamente(estilo) {
        const html =
            document.documentElement;

        if (estilosClaros.includes(estilo)) {
            html.setAttribute(
                'data-light-theme-style',
                estilo
            );

            return;
        }

        if (estilosOscuros.includes(estilo)) {
            html.setAttribute(
                'data-dark-theme-style',
                estilo
            );
        }
    }

    function actualizarSeleccionTema(estilo) {
        if (estilosClaros.includes(estilo)) {
            const estiloClaro =
                formulario.querySelector(
                    `input[name="light_theme_style"][value="${estilo}"]`
                );

            if (estiloClaro) {
                estiloClaro.checked = true;
            }

            aplicarTemaInmediatamente(
                estilo
            );

            return;
        }

        if (estilosOscuros.includes(estilo)) {
            const estiloOscuro =
                formulario.querySelector(
                    `input[name="dark_theme_style"][value="${estilo}"]`
                );

            if (estiloOscuro) {
                estiloOscuro.checked = true;
            }

            aplicarTemaInmediatamente(
                estilo
            );
        }
    }

    function guardarConfiguracion() {
        if (guardando) {
            guardadoPendiente = true;

            return;
        }

        guardando = true;

        peticion(
            formulario.action ||
            window.location.href,
            'POST',
            obtenerDatosFormulario()
        )
            .then((data) => {
                if (
                    data.settings &&
                    typeof window.aplicarConfiguracionGlobal === 'function'
                ) {
                    window.aplicarConfiguracionGlobal(
                        data.settings
                    );
                }

                window.showToast(
                    'success',
                    data.mensaje
                );
            })
            .catch((error) => {
                window.showToast(
                    'error',
                    error.message
                );
            })
            .finally(() => {
                guardando = false;

                if (guardadoPendiente) {
                    guardadoPendiente = false;

                    guardarConfiguracion();
                }
            });
    }

    function actualizarVistaLogo(archivo) {
        if (!archivo || !logoPreview) {
            return;
        }

        const lector = new FileReader();

        lector.onload = function (evento) {
            logoPreview.innerHTML = '';

            const imagen =
                document.createElement('img');

            imagen.src =
                evento.target.result;

            imagen.alt =
                'Vista previa del logotipo';

            logoPreview.appendChild(imagen);
        };

        lector.readAsDataURL(archivo);
    }

    if (logoInput && logoPreview) {
        logoInput.addEventListener('change', function () {
            const archivo = this.files[0];

            mostrarNombreLogo(
                archivo ? archivo.name : TEXTO_SIN_ARCHIVO
            );

            if (!archivo) {
                return;
            }

            if (!archivo.type.startsWith('image/')) {
                this.value = '';
                mostrarNombreLogo(TEXTO_SIN_ARCHIVO);

                window.showToast(
                    'error',
                    'El archivo seleccionado no es una imagen válida.'
                );

                return;
            }

            if (archivo.size > 2 * 1024 * 1024) {
                this.value = '';
                mostrarNombreLogo(TEXTO_SIN_ARCHIVO);

                window.showToast(
                    'error',
                    'El logotipo no puede superar los 2 MB.'
                );

                return;
            }

            actualizarVistaLogo(archivo);

            guardarConfiguracion();
        });
    }

    const estilosTema =
        formulario.querySelectorAll(
            'input[name="light_theme_style"], ' +
            'input[name="dark_theme_style"]'
        );

    estilosTema.forEach(function (campo) {
        campo.addEventListener('change', function () {
            if (!this.checked) {
                return;
            }

            actualizarSeleccionTema(
                this.value
            );

            guardarConfiguracion();
        });
    });

    // El modo claro / oscuro siempre refleja el tema que se está mostrando
    // (incluido lo que se elija con el interruptor de la barra superior).
    function sincronizarModoConTema() {
        const radio = formulario.querySelector(
            `input[name="theme_mode"][value="${obtenerModoActual()}"]`
        );

        if (radio && !radio.checked) {
            radio.checked = true;
        }
    }

    sincronizarModoConTema();

    document.addEventListener(
        'ironpulse:theme-changed',
        sincronizarModoConTema
    );

    formulario
        .querySelectorAll('input[name="theme_mode"]')
        .forEach(function (campo) {
            campo.addEventListener('change', function () {
                if (!this.checked) {
                    return;
                }

                if (typeof window.cambiarTemaManual === 'function') {
                    window.cambiarTemaManual(this.value);
                }

                guardarConfiguracion();
            });
        });

    const camposAutomaticos =
        formulario.querySelectorAll(
            'input[name="accent_color"], ' +
            'select[name="currency"], ' +
            'select[name="timezone"], ' +
            'select[name="date_format"], ' +
            'select[name="time_format"]'
        );

    camposAutomaticos.forEach(function (campo) {
        campo.addEventListener('change', function () {
            guardarConfiguracion();
        });
    });

    const nombreSistema =
        document.getElementById('system_name');

    if (nombreSistema) {
        nombreSistema.addEventListener('blur', function () {
            if (!this.value.trim()) {
                return;
            }

            guardarConfiguracion();
        });
    }
});
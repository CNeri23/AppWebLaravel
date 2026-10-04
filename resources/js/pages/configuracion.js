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

    function obtenerDatosFormulario(nombreCampo = null) {
        const datos = new FormData();

        datos.append('_method', 'PUT');

        if (nombreCampo) {
            const campo =
                formulario.querySelector(
                    `[name="${nombreCampo}"]:checked`
                ) ||
                formulario.querySelector(
                    `[name="${nombreCampo}"]`
                );

            if (campo) {
                if (
                    campo.type === 'radio' ||
                    campo.type === 'checkbox'
                ) {
                    if (campo.checked) {
                        datos.append(
                            campo.name,
                            campo.type === 'checkbox'
                                ? (campo.checked ? '1' : '0')
                                : campo.value
                        );
                    }
                } else if (campo.type === 'file') {
                    if (campo.files[0]) {
                        datos.append(
                            campo.name,
                            campo.files[0]
                        );
                    }
                } else {
                    datos.append(
                        campo.name,
                        campo.value
                    );
                }
            }

            return datos;
        }

        return new FormData(formulario);
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

    function guardarConfiguracion(nombreCampo = null) {
        if (guardando) {
            guardadoPendiente = nombreCampo;

            return;
        }

        guardando = true;

        peticion(
            formulario.action ||
            window.location.href,
            'POST',
            obtenerDatosFormulario(nombreCampo)
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
                    const campoPendiente =
                        guardadoPendiente;

                    guardadoPendiente = false;

                    guardarConfiguracion(
                        campoPendiente
                    );
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

            guardarConfiguracion('logo');
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

            guardarConfiguracion(
                this.name
            );
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

                guardarConfiguracion(
                    this.name
                );
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
            guardarConfiguracion(
                this.name
            );
        });
    });

    const nombreSistema =
        document.getElementById('system_name');

    if (nombreSistema) {
        nombreSistema.addEventListener('blur', function () {
            if (!this.value.trim()) {
                return;
            }

            guardarConfiguracion(
                this.name
            );
        });
    }


    // ---------------------------------------------------------------
    // Datos del negocio y Seguridad: cada campo se guarda solo, con Enter o
    // con la palomita dentro del input; los interruptores, al cambiarlos.
    // Cada guardado muestra su propio mensaje.
    // ---------------------------------------------------------------
    function inicializarCampos(tarjeta) {
        if (!tarjeta) {
            return;
        }

        function limpiarError(campo) {
            campo.classList.remove('is-invalid');

            const contenedor = campo.closest('.config-field');
            const mensaje = contenedor
                ? contenedor.querySelector('.invalid-feedback')
                : null;

            if (mensaje) {
                mensaje.remove();
            }
        }

        function mostrarError(campo, texto) {
            limpiarError(campo);

            campo.classList.add('is-invalid');

            const mensaje = document.createElement('div');

            mensaje.className = 'invalid-feedback d-block';
            mensaje.textContent = texto;

            const referencia = campo.closest('.config-input') || campo;

            referencia.insertAdjacentElement('afterend', mensaje);
        }

        async function enviar(nombre, valor) {
            const datos = new FormData();

            datos.append('_method', 'PUT');
            datos.append(nombre, valor);

            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: datos,
            });

            const data = await response.json().catch(() => ({}));

            if (response.status === 422 && data.errors) {
                const error = new Error(
                    (data.errors[nombre] || Object.values(data.errors).flat())[0]
                );

                error.validacion = true;

                throw error;
            }

            if (!response.ok || data.success === false) {
                throw new Error(
                    data.mensaje ||
                    data.message ||
                    'Ocurrió un error al guardar el cambio.'
                );
            }

            return data;
        }

        function mensajeDeError(error) {
            return error instanceof TypeError
                ? 'No se pudo conectar con el servidor. Intenta de nuevo.'
                : error.message;
        }

        function aplicarGlobal(nombre, settings) {
            if (
                nombre === 'session_timeout' &&
                settings &&
                typeof window.aplicarConfiguracionGlobal === 'function'
            ) {
                window.aplicarConfiguracionGlobal({
                    session_timeout: settings.session_timeout,
                });
            }
        }

        // ---- Campos de texto y número (Enter o palomita) ----
        tarjeta.querySelectorAll('.config-input').forEach(function (envoltura) {
            const campo = envoltura.querySelector('.form-control');
            const boton = envoltura.querySelector('.config-input-save');

            if (!campo || !boton) {
                return;
            }

            let guardado = campo.value;
            let enCurso = false;

            function esperado() {
                return campo.value.trim();
            }

            function refrescar() {
                boton.hidden = enCurso ? false : esperado() === guardado.trim();
            }

            async function guardar() {
                if (enCurso || esperado() === guardado.trim()) {
                    return;
                }

                enCurso = true;

                const icono = boton.innerHTML;

                boton.disabled = true;
                boton.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

                limpiarError(campo);

                try {
                    const data = await enviar(campo.name, esperado());

                    if (
                        data.settings &&
                        Object.prototype.hasOwnProperty.call(data.settings, campo.name)
                    ) {
                        campo.value = data.settings[campo.name] ?? '';
                    }

                    guardado = campo.value;

                    campo.classList.add('is-saved');

                    setTimeout(function () {
                        campo.classList.remove('is-saved');
                    }, 1400);

                    aplicarGlobal(campo.name, data.settings);

                    window.showToast('success', data.mensaje);
                } catch (error) {
                    if (error.validacion) {
                        mostrarError(campo, error.message);
                    }

                    window.showToast('error', mensajeDeError(error));
                } finally {
                    enCurso = false;
                    boton.disabled = false;
                    boton.innerHTML = icono;
                    refrescar();
                }
            }

            campo.addEventListener('input', function () {
                limpiarError(campo);
                refrescar();
            });

            campo.addEventListener('keydown', function (evento) {
                if (evento.isComposing) {
                    return;
                }

                const esArea = campo.tagName === 'TEXTAREA';

                if (
                    evento.key === 'Enter' &&
                    (!esArea || !evento.shiftKey)
                ) {
                    evento.preventDefault();
                    guardar();

                    return;
                }

                if (evento.key === 'Escape') {
                    campo.value = guardado;
                    limpiarError(campo);
                    refrescar();
                }
            });

            boton.addEventListener('click', guardar);

            refrescar();
        });

        // ---- Interruptores: se guardan al cambiar ----
        tarjeta.querySelectorAll('.form-switch input[type="checkbox"]').forEach(function (interruptor) {
            interruptor.addEventListener('change', async function () {
                const nuevo = interruptor.checked;

                interruptor.disabled = true;

                try {
                    const data = await enviar(interruptor.name, nuevo ? '1' : '0');

                    aplicarGlobal(interruptor.name, data.settings);

                    window.showToast('success', data.mensaje);
                } catch (error) {
                    interruptor.checked = !nuevo;

                    window.showToast('error', mensajeDeError(error));
                } finally {
                    interruptor.disabled = false;
                }
            });
        });
    }

    inicializarCampos(document.getElementById('formNegocio'));
    inicializarCampos(document.getElementById('formSeguridad'));
});
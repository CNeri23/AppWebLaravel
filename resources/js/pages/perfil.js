document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement =
        document.querySelector('meta[name="csrf-token"]');

    const csrfToken =
        csrfTokenElement
            ? csrfTokenElement.content
            : '';

    const modalEditarPerfilEl =
        document.getElementById('modalEditarPerfil');

    const modalCambiarPasswordEl =
        document.getElementById('modalCambiarPassword');

    const modalFotoPerfilEl =
        document.getElementById('modalFotoPerfil');

    const modalEditarPerfil =
        modalEditarPerfilEl
            ? new bootstrap.Modal(modalEditarPerfilEl)
            : null;

    const modalCambiarPassword =
        modalCambiarPasswordEl
            ? new bootstrap.Modal(modalCambiarPasswordEl)
            : null;

    const modalFotoPerfil =
        modalFotoPerfilEl
            ? new bootstrap.Modal(modalFotoPerfilEl)
            : null;

    const formEditarPerfil =
        document.getElementById('formEditarPerfil');

    const formCambiarPassword =
        document.getElementById('formCambiarPassword');

    const formFotoPerfil =
        document.getElementById('formFotoPerfil');

    const btnGuardarPerfil =
        document.getElementById('btnGuardarPerfil');

    const btnCambiarPassword =
        document.getElementById('btnCambiarPassword');

    const btnGuardarFoto =
        document.getElementById('btnGuardarFoto');

    const btnEliminarFoto =
        document.getElementById('btnEliminarFoto');

    const profileName =
        document.getElementById('profileName');

    const profileEmail =
        document.getElementById('profileEmail');

    const profileInfoEmail =
        document.getElementById('profileInfoEmail');

    const profileImageInput =
        document.getElementById('profile_image');

    const photoPreview =
        document.getElementById('photoPreview');

    function peticion(url, method, body = null) {
        const opciones = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        if (body instanceof FormData) {
            opciones.body = body;
        } else if (body) {
            opciones.headers['Content-Type'] =
                'application/json';

            opciones.body =
                JSON.stringify(body);
        }

        return fetch(url, opciones)
            .then(async function (response) {
                const data =
                    await response
                        .json()
                        .catch(() => ({}));

                if (
                    !response.ok ||
                    data.success === false
                ) {
                    const primerError =
                        data.errors
                            ? Object.values(data.errors).flat()[0]
                            : null;

                    const error = new Error(
                        primerError ||
                        data.mensaje ||
                        data.message ||
                        'Ocurrió un error al procesar la solicitud.'
                    );

                    if (data.errors) {
                        error.errors = data.errors;
                    }

                    throw error;
                }

                return data;
            });
    }

    function mostrarError(error) {
        window.showToast(
            'error',
            error.message
        );
    }

    function mostrarExito(mensaje) {
        window.showToast(
            'success',
            mensaje
        );
    }

    function limpiarErrores(formulario) {
        if (!formulario) {
            return;
        }

        formulario
            .querySelectorAll('.is-invalid')
            .forEach(function (elemento) {
                elemento.classList.remove(
                    'is-invalid'
                );
            });

        formulario
            .querySelectorAll('.invalid-feedback')
            .forEach(function (elemento) {
                elemento.textContent = '';
            });
    }

    function mostrarErrores(
        formulario,
        errores
    ) {
        if (!formulario || !errores) {
            return;
        }

        Object.keys(errores).forEach(
            function (campo) {
                const input =
                    formulario.querySelector(
                        '[name="' + campo + '"]'
                    );

                const error =
                    document.getElementById(
                        obtenerIdError(campo)
                    );

                if (input) {
                    input.classList.add(
                        'is-invalid'
                    );
                }

                if (
                    error &&
                    errores[campo] &&
                    errores[campo].length
                ) {
                    error.textContent =
                        errores[campo][0];
                }
            }
        );
    }

    function obtenerIdError(campo) {
        const ids = {
            name: 'perfil-name-error',
            email: 'perfil-email-error',
            current_password: 'current-password-error',
            password: 'profile-password-error',
            password_confirmation:
                'profile-password-confirmation-error',
            profile_image:
                'profile-image-error',
        };

        return (
            ids[campo] ||
            campo.replaceAll('_', '-') +
            '-error'
        );
    }

    function cambiarEstadoBoton(
        boton,
        cargando,
        textoCargando
    ) {
        if (!boton) {
            return;
        }

        if (cargando) {
            if (!boton.dataset.htmlOriginal) {
                boton.dataset.htmlOriginal =
                    boton.innerHTML;
            }

            boton.disabled = true;

            boton.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                (textoCargando || 'Guardando...');
        } else {
            boton.disabled = false;

            if (boton.dataset.htmlOriginal) {
                boton.innerHTML =
                    boton.dataset.htmlOriginal;
            }
        }
    }

    function cerrarModal(modal) {
        if (modal) {
            modal.hide();
        }
    }

    function formatearFecha(
        fecha
    ) {
        if (!fecha) {
            return '';
        }

        const fechaObjeto =
            new Date(fecha);

        if (
            isNaN(
                fechaObjeto.getTime()
            )
        ) {
            return '';
        }

        const dia =
            String(
                fechaObjeto.getDate()
            ).padStart(2, '0');

        const mes =
            String(
                fechaObjeto.getMonth() + 1
            ).padStart(2, '0');

        const año =
            fechaObjeto.getFullYear();

        const horas =
            String(
                fechaObjeto.getHours()
            ).padStart(2, '0');

        const minutos =
            String(
                fechaObjeto.getMinutes()
            ).padStart(2, '0');

        return (
            dia +
            '/' +
            mes +
            '/' +
            año +
            ' ' +
            horas +
            ':' +
            minutos
        );
    }

    function tiempoRelativo(
        fecha
    ) {
        if (!fecha) {
            return 'Sin información';
        }

        const fechaObjeto =
            new Date(fecha);

        if (
            isNaN(
                fechaObjeto.getTime()
            )
        ) {
            return 'Sin información';
        }

        const ahora =
            new Date();

        const diferencia =
            Math.floor(
                (
                    ahora.getTime() -
                    fechaObjeto.getTime()
                ) / 1000
            );

        if (diferencia < 10) {
            return 'Hace unos segundos';
        }

        if (diferencia < 60) {
            return (
                'Hace ' +
                diferencia +
                ' segundos'
            );
        }

        const minutos =
            Math.floor(
                diferencia / 60
            );

        if (minutos < 60) {
            return (
                'Hace ' +
                minutos +
                (
                    minutos === 1
                        ? ' minuto'
                        : ' minutos'
                )
            );
        }

        const horas =
            Math.floor(
                minutos / 60
            );

        if (horas < 24) {
            return (
                'Hace ' +
                horas +
                (
                    horas === 1
                        ? ' hora'
                        : ' horas'
                )
            );
        }

        const dias =
            Math.floor(
                horas / 24
            );

        if (dias < 30) {
            return (
                'Hace ' +
                dias +
                (
                    dias === 1
                        ? ' día'
                        : ' días'
                )
            );
        }

        const meses =
            Math.floor(
                dias / 30
            );

        if (meses < 12) {
            return (
                'Hace ' +
                meses +
                (
                    meses === 1
                        ? ' mes'
                        : ' meses'
                )
            );
        }

        const años =
            Math.floor(
                meses / 12
            );

        return (
            'Hace ' +
            años +
            (
                años === 1
                    ? ' año'
                    : ' años'
            )
        );
    }

    function actualizarFechaActividad(
        fecha
    ) {
        const elementos =
            document.querySelectorAll(
                '[data-profile-updated]'
            );

        elementos.forEach(
            function (elemento) {
                elemento.dataset.profileUpdated =
                    fecha;

                elemento.textContent =
                    tiempoRelativo(fecha);
            }
        );
    }

    function actualizarInformacionPerfil(
        usuario
    ) {
        if (!usuario) {
            return;
        }

        if (profileName) {
            profileName.textContent =
                usuario.name;
        }

        if (profileEmail) {
            profileEmail.innerHTML =
                '<i class="fa-solid fa-envelope me-1"></i> ' +
                escapeHtml(usuario.email);
        }

        if (profileInfoEmail) {
            profileInfoEmail.textContent =
                usuario.email;
        }

        if (usuario.updated_at) {
            actualizarFechaActividad(
                usuario.updated_at
            );
        }
    }

    function reemplazarAvatarElemento(
        id,
        claseBase,
        clasePlaceholder,
        imagenUrl,
        inicial
    ) {
        const elemento =
            document.getElementById(id);

        if (!elemento) {
            return;
        }

        if (imagenUrl) {
            const imagen =
                document.createElement(
                    'img'
                );

            imagen.src =
                imagenUrl;

            imagen.alt =
                'Foto de perfil';

            imagen.className =
                claseBase;

            imagen.id =
                id;

            elemento.replaceWith(
                imagen
            );

            return;
        }

        const placeholder =
            document.createElement(
                'div'
            );

        placeholder.className =
            claseBase +
            (clasePlaceholder ? ' ' + clasePlaceholder : '');

        placeholder.id =
            id;

        placeholder.textContent =
            inicial || '';

        elemento.replaceWith(
            placeholder
        );
    }

    function actualizarAvatar(
        imagenUrl,
        inicial
    ) {
        // Avatar grande de esta página.
        reemplazarAvatarElemento(
            'profileAvatar',
            'profile-avatar',
            'profile-avatar-placeholder',
            imagenUrl,
            inicial
        );

        // Avatar del menú de usuario en el topbar, para que no quede
        // desincronizado con la foto recién subida/eliminada.
        reemplazarAvatarElemento(
            'topbarUserAvatar',
            'user-avatar',
            '',
            imagenUrl,
            inicial
        );
    }

    function actualizarPreview(
        imagenUrl,
        inicial
    ) {
        const preview =
            document.getElementById(
                'photoPreview'
            );

        if (!preview) {
            return;
        }

        if (imagenUrl) {
            if (
                preview.tagName ===
                'IMG'
            ) {
                preview.src =
                    imagenUrl;

                return;
            }

            const imagen =
                document.createElement(
                    'img'
                );

            imagen.src =
                imagenUrl;

            imagen.alt =
                'Foto de perfil';

            imagen.id =
                'photoPreview';

            preview.replaceWith(
                imagen
            );

            return;
        }

        const placeholder =
            document.createElement(
                'div'
            );

        placeholder.className =
            'profile-photo-preview-placeholder';

        placeholder.id =
            'photoPreview';

        placeholder.textContent =
            inicial || '';

        preview.replaceWith(
            placeholder
        );
    }

    function escapeHtml(
        valor
    ) {
        const div =
            document.createElement(
                'div'
            );

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    // Reglas espejo de las validaciones del ProfileController (Laravel).
    // La unicidad de email y la verificación de current_password solo
    // puede validarlas el backend; aquí solo se valida lo verificable
    // en el cliente para dar feedback inmediato.
    const validadores = {
        name: function (valor) {
            valor = valor.trim();

            if (!valor) {
                return 'El nombre es obligatorio.';
            }

            if (valor.length > 255) {
                return 'El nombre no puede superar los 255 caracteres.';
            }

            return '';
        },
        email: function (valor) {
            valor = valor.trim();

            if (!valor) {
                return 'El correo electrónico es obligatorio.';
            }

            if (valor.length > 255) {
                return 'El correo electrónico no puede superar los 255 caracteres.';
            }

            const patronEmail =
                /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (!patronEmail.test(valor)) {
                return 'Ingresa un correo electrónico válido.';
            }

            return '';
        },
        current_password: function (valor) {
            if (!valor) {
                return 'La contraseña actual es obligatoria.';
            }

            return '';
        },
        password: function (valor) {
            if (!valor) {
                return 'La nueva contraseña es obligatoria.';
            }

            // Política de Configuración > Seguridad (la imprime index.blade.php)
            const politica = window.politicaPassword || {};
            const minimo = parseInt(politica.min, 10) || 8;

            if (valor.length < minimo) {
                return 'La nueva contraseña debe tener al menos ' +
                    minimo + ' caracteres.';
            }

            if (
                politica.complex &&
                !(
                    /\p{Ll}/u.test(valor) &&
                    /\p{Lu}/u.test(valor) &&
                    /\d/.test(valor)
                )
            ) {
                return 'La contraseña debe incluir al menos una mayúscula, una minúscula y un número.';
            }

            return '';
        },
        password_confirmation: function (valor, formulario) {
            if (!valor) {
                return 'Confirma la nueva contraseña.';
            }

            const nueva =
                formulario.querySelector(
                    '[name="password"]'
                );

            if (nueva && valor !== nueva.value) {
                return 'Las contraseñas no coinciden.';
            }

            return '';
        },
    };

    function validarCampo(
        formulario,
        campo
    ) {
        const input =
            formulario.querySelector(
                '[name="' + campo + '"]'
            );

        const validador =
            validadores[campo];

        if (!input || !validador) {
            return true;
        }

        const mensaje =
            validador(input.value, formulario);

        const error =
            document.getElementById(
                obtenerIdError(campo)
            );

        if (mensaje) {
            input.classList.add('is-invalid');

            if (error) {
                error.textContent = mensaje;
            }

            return false;
        }

        input.classList.remove('is-invalid');

        if (error) {
            error.textContent = '';
        }

        return true;
    }

    function validarFormulario(
        formulario,
        campos
    ) {
        let esValido = true;

        campos.forEach(function (campo) {
            if (!validarCampo(formulario, campo)) {
                esValido = false;
            }
        });

        return esValido;
    }

    function activarValidacionEnTiempoReal(
        formulario,
        campos
    ) {
        if (!formulario) {
            return;
        }

        campos.forEach(function (campo) {
            const input =
                formulario.querySelector(
                    '[name="' + campo + '"]'
                );

            if (!input) {
                return;
            }

            input.addEventListener('input', function () {
                validarCampo(formulario, campo);

                // Si cambia la nueva contraseña, revalida también
                // la confirmación para mantenerlas sincronizadas.
                if (campo === 'password') {
                    const confirmacion =
                        formulario.querySelector(
                            '[name="password_confirmation"]'
                        );

                    if (confirmacion && confirmacion.value) {
                        validarCampo(
                            formulario,
                            'password_confirmation'
                        );
                    }
                }
            });

            input.addEventListener('blur', function () {
                validarCampo(formulario, campo);
            });
        });
    }

    // Helper genérico: evita repetir en cada form el mismo bloque
    // de preventDefault + limpiar errores + validar + spinner del
    // botón + fetch + éxito/error/finally.
    function configurarEnvioFormulario(opciones) {
        const {
            formulario,
            boton,
            campos,
            textoCargando,
            antesDeEnviar,
            alExito,
        } = opciones;

        if (!formulario) {
            return;
        }

        formulario.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                limpiarErrores(formulario);

                if (
                    campos &&
                    !validarFormulario(formulario, campos)
                ) {
                    window.showToast(
                        'error',
                        'Corrige los campos marcados antes de continuar.'
                    );

                    return;
                }

                if (
                    typeof antesDeEnviar === 'function' &&
                    antesDeEnviar(formulario) === false
                ) {
                    return;
                }

                cambiarEstadoBoton(
                    boton,
                    true,
                    textoCargando
                );

                const formData =
                    new FormData(formulario);

                peticion(
                    formulario.action,
                    'POST',
                    formData
                )
                    .then(function (data) {
                        if (typeof alExito === 'function') {
                            alExito(data);
                        }
                    })
                    .catch(function (error) {
                        if (error.errors) {
                            mostrarErrores(
                                formulario,
                                error.errors
                            );
                        }

                        mostrarError(error);
                    })
                    .finally(function () {
                        cambiarEstadoBoton(
                            boton,
                            false
                        );
                    });
            }
        );
    }

    document.addEventListener(
        'click',
        function (event) {
            const toggle =
                event.target.closest(
                    '.toggle-password'
                );

            if (!toggle) {
                return;
            }

            const targetId =
                toggle.dataset.passwordTarget;

            const input =
                document.getElementById(
                    targetId
                );

            if (!input) {
                return;
            }

            const icon =
                toggle.querySelector(
                    'i'
                );

            if (
                input.type ===
                'password'
            ) {
                input.type =
                    'text';

                if (icon) {
                    icon.classList.remove(
                        'fa-eye'
                    );

                    icon.classList.add(
                        'fa-eye-slash'
                    );
                }

                toggle.setAttribute(
                    'aria-label',
                    'Ocultar contraseña'
                );
            } else {
                input.type =
                    'password';

                if (icon) {
                    icon.classList.remove(
                        'fa-eye-slash'
                    );

                    icon.classList.add(
                        'fa-eye'
                    );
                }

                toggle.setAttribute(
                    'aria-label',
                    'Mostrar contraseña'
                );
            }
        }
    );

    if (modalEditarPerfilEl) {
        modalEditarPerfilEl.addEventListener(
            'show.bs.modal',
            function () {
                limpiarErrores(
                    formEditarPerfil
                );
            }
        );
    }

    if (modalCambiarPasswordEl) {
        modalCambiarPasswordEl.addEventListener(
            'show.bs.modal',
            function () {
                limpiarErrores(
                    formCambiarPassword
                );
            }
        );

        modalCambiarPasswordEl.addEventListener(
            'hidden.bs.modal',
            function () {
                if (formCambiarPassword) {
                    formCambiarPassword.reset();

                    limpiarErrores(
                        formCambiarPassword
                    );
                }
            }
        );
    }

    if (modalFotoPerfilEl) {
        modalFotoPerfilEl.addEventListener(
            'show.bs.modal',
            function () {
                limpiarErrores(
                    formFotoPerfil
                );
            }
        );

        modalFotoPerfilEl.addEventListener(
            'hidden.bs.modal',
            function () {
                if (profileImageInput) {
                    profileImageInput.value =
                        '';
                }

                limpiarErrores(
                    formFotoPerfil
                );
            }
        );
    }

    activarValidacionEnTiempoReal(
        formEditarPerfil,
        ['name', 'email']
    );

    activarValidacionEnTiempoReal(
        formCambiarPassword,
        ['current_password', 'password', 'password_confirmation']
    );

    if (
        profileImageInput
    ) {
        profileImageInput.addEventListener(
            'change',
            function () {
                limpiarErrores(
                    formFotoPerfil
                );

                const archivo =
                    this.files[0];

                if (!archivo) {
                    return;
                }

                const tiposPermitidos = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];

                if (
                    !tiposPermitidos.includes(
                        archivo.type
                    )
                ) {
                    this.value =
                        '';

                    window.showToast(
                        'error',
                        'La imagen debe ser JPG, JPEG, PNG o WEBP.'
                    );

                    return;
                }

                if (
                    archivo.size >
                    2 * 1024 * 1024
                ) {
                    this.value =
                        '';

                    window.showToast(
                        'error',
                        'La imagen no puede superar los 2 MB.'
                    );

                    return;
                }

                const lector =
                    new FileReader();

                lector.onload =
                    function (event) {
                        actualizarPreview(
                            event.target.result,
                            ''
                        );
                    };

                lector.readAsDataURL(
                    archivo
                );
            }
        );
    }

    configurarEnvioFormulario({
        formulario: formEditarPerfil,
        boton: btnGuardarPerfil,
        campos: ['name', 'email'],
        textoCargando: 'Guardando...',
        antesDeEnviar: function (formulario) {
            const inputName =
                formulario.querySelector('[name="name"]');

            const inputEmail =
                formulario.querySelector('[name="email"]');

            const sinCambios =
                inputName.value.trim() === inputName.defaultValue.trim() &&
                inputEmail.value.trim() === inputEmail.defaultValue.trim();

            if (sinCambios) {
                window.showToast(
                    'info',
                    'No hubo cambios para actualizar.'
                );

                return false;
            }

            return true;
        },
        alExito: function (data) {
            actualizarInformacionPerfil(
                data.usuario
            );

            // Sincroniza los valores "originales" para que la próxima
            // vez que se abra el modal, la comparación de cambios use
            // los datos ya guardados.
            const inputName =
                formEditarPerfil.querySelector('[name="name"]');

            const inputEmail =
                formEditarPerfil.querySelector('[name="email"]');

            if (inputName) {
                inputName.defaultValue = inputName.value;
            }

            if (inputEmail) {
                inputEmail.defaultValue = inputEmail.value;
            }

            cerrarModal(
                modalEditarPerfil
            );

            mostrarExito(
                data.mensaje ||
                'Perfil actualizado correctamente.'
            );
        },
    });

    configurarEnvioFormulario({
        formulario: formCambiarPassword,
        boton: btnCambiarPassword,
        campos: ['current_password', 'password', 'password_confirmation'],
        textoCargando: 'Guardando...',
        alExito: function (data) {
            if (
                data.usuario &&
                data.usuario.updated_at
            ) {
                actualizarFechaActividad(
                    data.usuario.updated_at
                );
            }

            cerrarModal(
                modalCambiarPassword
            );

            formCambiarPassword.reset();

            mostrarExito(
                data.mensaje ||
                'Contraseña actualizada correctamente.'
            );
        },
    });

    configurarEnvioFormulario({
        formulario: formFotoPerfil,
        boton: btnGuardarFoto,
        textoCargando: 'Subiendo...',
        antesDeEnviar: function () {
            if (
                !profileImageInput ||
                !profileImageInput.files.length
            ) {
                window.showToast(
                    'error',
                    'Selecciona una imagen.'
                );

                return false;
            }

            return true;
        },
        alExito: function (data) {
            const usuario =
                data.usuario;

            actualizarAvatar(
                data.imagen,
                usuario?.name
                    ? usuario.name
                        .charAt(0)
                        .toUpperCase()
                    : ''
            );

            actualizarPreview(
                data.imagen,
                ''
            );

            if (
                usuario &&
                usuario.updated_at
            ) {
                actualizarFechaActividad(
                    usuario.updated_at
                );
            }

            cerrarModal(
                modalFotoPerfil
            );

            mostrarExito(
                data.mensaje ||
                'Foto de perfil actualizada correctamente.'
            );
        },
    });

    if (btnEliminarFoto) {
        btnEliminarFoto.addEventListener(
            'click',
            function () {
                const boton =
                    this;

                const url =
                    boton.dataset.url;

                if (!url) {
                    return;
                }

                const temaOscuro =
                    document.documentElement.getAttribute('data-bs-theme') === 'dark';

                Swal.fire({
                    icon: 'warning',
                    title: '¿Eliminar foto?',
                    text: 'Se eliminará tu foto de perfil.',
                    showCancelButton: true,
                    confirmButtonText: 'Eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true,
                    focusCancel: true,
                    width: '360px',
                    padding: '1.25rem',
                    background: temaOscuro ? '#111827' : '#ffffff',
                    color: temaOscuro ? '#f8fafc' : '#1f2937',
                    customClass: {
                        popup: temaOscuro
                            ? 'swal-confirmacion swal-confirmacion-dark'
                            : 'swal-confirmacion swal-confirmacion-light',
                        title: 'swal-confirmacion-titulo',
                        htmlContainer: 'swal-confirmacion-texto',
                        confirmButton: 'swal-confirmacion-confirmar',
                        cancelButton: 'swal-confirmacion-cancelar'
                    }
                }).then(function (resultado) {
                    if (!resultado.isConfirmed) {
                        return;
                    }

                    cambiarEstadoBoton(
                        boton,
                        true,
                        'Eliminando...'
                    );

                    peticion(
                        url,
                        'DELETE'
                    )
                        .then(function (data) {
                            const usuario =
                                data.usuario;

                            const inicial =
                                usuario?.name
                                    ? usuario.name
                                        .charAt(0)
                                        .toUpperCase()
                                    : profileName
                                        ?.textContent
                                        .trim()
                                        .charAt(0)
                                        .toUpperCase();

                            actualizarAvatar(
                                null,
                                inicial
                            );

                            actualizarPreview(
                                null,
                                inicial
                            );

                            if (
                                usuario &&
                                usuario.updated_at
                            ) {
                                actualizarFechaActividad(
                                    usuario.updated_at
                                );
                            }

                            cerrarModal(
                                modalFotoPerfil
                            );

                            mostrarExito(
                                data.mensaje ||
                                'Foto de perfil eliminada correctamente.'
                            );
                        })
                        .catch(function (error) {
                            mostrarError(
                                error
                            );
                        })
                        .finally(function () {
                            cambiarEstadoBoton(
                                boton,
                                false
                            );
                        });
                });
            }
        );
    }

    setInterval(
        function () {
            document
                .querySelectorAll(
                    '[data-profile-updated]'
                )
                .forEach(function (elemento) {
                    elemento.textContent =
                        tiempoRelativo(
                            elemento.dataset.profileUpdated
                        );
                });
        },
        10000
    );
});
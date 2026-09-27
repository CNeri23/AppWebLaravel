document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    const authFormStage = document.getElementById('authFormStage');
    const formLogin = document.getElementById('formLogin');
    const formRegister = document.getElementById('formRegister');
    const formForgot = document.getElementById('formForgot');
    const formResetPassword = document.getElementById('formResetPassword');

    if (!authFormStage || !formLogin) {
        return;
    }

    const panels = document.querySelectorAll('.auth-panel');
    const authLinks = document.querySelectorAll('[data-auth-target]');

    const PANEL_KEY = 'authCurrentPanel';

    const panelInicialServidor =
        authFormStage.dataset.initialPanel || 'login';

    let panelActual =
        panelInicialServidor !== 'login'
            ? panelInicialServidor
            : localStorage.getItem(PANEL_KEY) || 'login';

    let cambiandoPanel = false;

    function obtenerPanel(nombre) {
        return document.querySelector(`[data-panel="${nombre}"]`);
    }

    function limpiarErroresPanel(panel) {
        if (!panel) {
            return;
        }

        panel.querySelectorAll('.form-control').forEach(function (input) {
            input.classList.remove('is-invalid', 'is-valid');
        });

        panel.querySelectorAll('.invalid-feedback').forEach(function (error) {
            error.textContent = '';
        });
    }

    function activarPanelInicial() {
        panels.forEach(function (panel) {
            panel.classList.remove(
                'auth-panel-active',
                'auth-panel-enter',
                'auth-panel-exit'
            );
        });

        let panelInicial = obtenerPanel(panelActual);

        if (!panelInicial) {
            panelActual = 'login';
            panelInicial = obtenerPanel('login');
        }

        if (!panelInicial) {
            return;
        }

        panelInicial.classList.add('auth-panel-active');

        localStorage.setItem(PANEL_KEY, panelActual);

        const primerInput = panelInicial.querySelector(
            'input:not([type="hidden"])'
        );

        if (primerInput && panelActual !== 'login') {
            setTimeout(function () {
                primerInput.focus();
            }, 100);
        }
    }

    function cambiarPanel(nombre, guardar = true) {
        if (cambiandoPanel || nombre === panelActual) {
            return;
        }

        const panelActualElement = obtenerPanel(panelActual);
        const nuevoPanel = obtenerPanel(nombre);

        if (!panelActualElement || !nuevoPanel) {
            return;
        }

        cambiandoPanel = true;

        limpiarErroresPanel(panelActualElement);
        limpiarErroresPanel(nuevoPanel);

        nuevoPanel.classList.remove(
            'auth-panel-active',
            'auth-panel-enter'
        );

        panelActualElement.classList.remove('auth-panel-enter');
        panelActualElement.classList.add('auth-panel-exit');

        setTimeout(function () {
            panelActualElement.classList.remove(
                'auth-panel-active',
                'auth-panel-exit'
            );

            nuevoPanel.classList.add(
                'auth-panel-active',
                'auth-panel-enter'
            );

            panelActual = nombre;

            if (guardar) {
                localStorage.setItem(PANEL_KEY, panelActual);
            }

            setTimeout(function () {
                nuevoPanel.classList.remove('auth-panel-enter');
                cambiandoPanel = false;

                const primerInput = nuevoPanel.querySelector(
                    'input:not([type="hidden"])'
                );

                if (primerInput) {
                    primerInput.focus();
                }
            }, 280);
        }, 140);
    }

    activarPanelInicial();

    authLinks.forEach(function (link) {
        link.addEventListener('click', function () {
            cambiarPanel(link.dataset.authTarget);
        });
    });

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const rememberInput = document.getElementById('remember');

    const REMEMBER_KEY = 'loginRememberedEmail';

    const emailGuardado = localStorage.getItem(REMEMBER_KEY);

    if (emailGuardado && rememberInput && emailInput) {
        emailInput.value = emailGuardado;
        rememberInput.checked = true;
    }

    function limpiarErroresLogin() {
        if (emailInput) {
            emailInput.classList.remove('is-invalid', 'is-valid');
        }

        if (passwordInput) {
            passwordInput.classList.remove('is-invalid', 'is-valid');
        }

        if (emailError) {
            emailError.textContent = '';
        }

        if (passwordError) {
            passwordError.textContent = '';
        }
    }

    function mostrarErroresDeCampos(errors) {
        let primerCampoInvalido = null;

        if (errors.email && emailInput && emailError) {
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
            emailError.textContent = errors.email[0];

            primerCampoInvalido =
                primerCampoInvalido || emailInput;
        }

        if (errors.password && passwordInput && passwordError) {
            passwordInput.classList.remove('is-valid');
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = errors.password[0];

            primerCampoInvalido =
                primerCampoInvalido || passwordInput;
        }

        if (primerCampoInvalido) {
            primerCampoInvalido.focus();
        }
    }

    function validarEnVivo(input, errorDiv) {
        if (!input || !errorDiv) {
            return;
        }

        const tieneValor = input.value.trim() !== '';
        const esValido = tieneValor && input.checkValidity();

        if (esValido) {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            errorDiv.textContent = '';
        } else {
            input.classList.remove('is-valid');

            if (!tieneValor) {
                input.classList.remove('is-invalid');
                errorDiv.textContent = '';
            }
        }
    }

    if (emailInput) {
        emailInput.addEventListener('input', function () {
            validarEnVivo(emailInput, emailError);
        });
    }

    if (passwordInput) {
        passwordInput.addEventListener('input', function () {
            validarEnVivo(passwordInput, passwordError);
        });
    }

    document.querySelectorAll('[data-password-target]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = button.dataset.passwordTarget;
            const input = document.getElementById(targetId);
            const icon = button.querySelector('i');

            if (!input || !icon) {
                return;
            }

            const seVaAMostrar = input.type === 'password';

            input.type = seVaAMostrar ? 'text' : 'password';

            icon.classList.toggle('fa-eye', !seVaAMostrar);
            icon.classList.toggle('fa-eye-slash', seVaAMostrar);

            button.setAttribute(
                'aria-label',
                seVaAMostrar
                    ? 'Ocultar contraseña'
                    : 'Mostrar contraseña'
            );
        });
    });

    const togglePasswordBtn =
        document.getElementById('togglePassword');

    if (togglePasswordBtn && passwordInput) {
        togglePasswordBtn.dataset.passwordTarget = 'password';
    }

    formLogin.addEventListener('submit', function (event) {
        event.preventDefault();

        limpiarErroresLogin();

        const botonSubmit =
            formLogin.querySelector('button[type="submit"]');

        const textoOriginal = botonSubmit.innerHTML;

        botonSubmit.disabled = true;
        botonSubmit.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Ingresando...';

        const formData = new FormData(formLogin);

        fetch(formLogin.action, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: formData,
        })
            .then(async function (response) {
                const data = await response.json().catch(function () {
                    return {};
                });

                if (response.status === 422 && data.errors) {
                    mostrarErroresDeCampos(data.errors);
                    return;
                }

                if (!response.ok || data.success === false) {
                    window.showToast(
                        'error',
                        data.mensaje ||
                        'Ocurrió un error al iniciar sesión.'
                    );

                    if (emailInput) {
                        emailInput.focus();
                    }

                    return;
                }

                if (rememberInput && rememberInput.checked) {
                    localStorage.setItem(
                        REMEMBER_KEY,
                        emailInput.value.trim()
                    );
                } else {
                    localStorage.removeItem(REMEMBER_KEY);
                }

                localStorage.removeItem(PANEL_KEY);

                setTimeout(function () {
                    window.location.href =
                        data.redirect || '/dashboard';
                }, 500);
            })
            .catch(function () {
                window.showToast(
                    'error',
                    'No se pudo conectar con el servidor. Intenta de nuevo.'
                );
            })
            .finally(function () {
                botonSubmit.disabled = false;
                botonSubmit.innerHTML = textoOriginal;
            });
    });

    if (formRegister) {
        const registerName =
            document.getElementById('registerName');

        const registerEmail =
            document.getElementById('registerEmail');

        const registerPassword =
            document.getElementById('registerPassword');

        const registerPasswordConfirmation =
            document.getElementById(
                'registerPasswordConfirmation'
            );

        formRegister.addEventListener('submit', function (event) {
            event.preventDefault();

            const botonSubmit =
                formRegister.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;
            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Creando cuenta...';

            limpiarErroresPanel(
                obtenerPanel('register')
            );

            const formData = new FormData(formRegister);

            fetch(formRegister.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then(async function (response) {
                    const data = await response.json().catch(function () {
                        return {};
                    });

                    if (response.status === 422 && data.errors) {
                        mostrarErroresRegistro(data.errors);
                        return;
                    }

                    if (!response.ok || data.success === false) {
                        window.showToast(
                            'error',
                            data.mensaje ||
                            'No se pudo crear la cuenta.'
                        );

                        return;
                    }

                    window.showToast(
                        'success',
                        data.mensaje ||
                        'Cuenta creada correctamente.'
                    );

                    const correoRegistrado =
                        registerEmail
                            ? registerEmail.value.trim()
                            : '';

                    formRegister.reset();

                    setTimeout(function () {
                        cambiarPanel('login');

                        if (emailInput && correoRegistrado) {
                            emailInput.value =
                                correoRegistrado;
                        }
                    }, 700);
                })
                .catch(function () {
                    window.showToast(
                        'error',
                        'No se pudo conectar con el servidor. Intenta de nuevo.'
                    );
                })
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });

        function mostrarErroresRegistro(errors) {
            let primerCampoInvalido = null;

            const campos = [
                {
                    key: 'name',
                    input: registerName,
                    error: document.getElementById(
                        'register-name-error'
                    )
                },
                {
                    key: 'email',
                    input: registerEmail,
                    error: document.getElementById(
                        'register-email-error'
                    )
                },
                {
                    key: 'password',
                    input: registerPassword,
                    error: document.getElementById(
                        'register-password-error'
                    )
                },
                {
                    key: 'password_confirmation',
                    input: registerPasswordConfirmation,
                    error: document.getElementById(
                        'register-password-confirmation-error'
                    )
                }
            ];

            campos.forEach(function (campo) {
                if (errors[campo.key]) {
                    campo.input.classList.add(
                        'is-invalid'
                    );

                    campo.error.textContent =
                        errors[campo.key][0];

                    primerCampoInvalido =
                        primerCampoInvalido ||
                        campo.input;
                }
            });

            if (primerCampoInvalido) {
                primerCampoInvalido.focus();
            }
        }

        [
            [
                registerName,
                document.getElementById(
                    'register-name-error'
                )
            ],
            [
                registerEmail,
                document.getElementById(
                    'register-email-error'
                )
            ],
            [
                registerPassword,
                document.getElementById(
                    'register-password-error'
                )
            ],
            [
                registerPasswordConfirmation,
                document.getElementById(
                    'register-password-confirmation-error'
                )
            ]
        ].forEach(function (campo) {
            if (campo[0]) {
                campo[0].addEventListener(
                    'input',
                    function () {
                        validarEnVivo(
                            campo[0],
                            campo[1]
                        );
                    }
                );
            }
        });
    }

    if (formForgot) {
        const forgotEmail =
            document.getElementById('forgotEmail');

        const forgotEmailError =
            document.getElementById(
                'forgot-email-error'
            );

        formForgot.addEventListener('submit', function (event) {
            event.preventDefault();

            forgotEmail.classList.remove(
                'is-invalid',
                'is-valid'
            );

            forgotEmailError.textContent = '';

            const botonSubmit =
                formForgot.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;
            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';

            const formData = new FormData(formForgot);

            fetch(formForgot.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            })
                .then(async function (response) {
                    const data = await response.json().catch(function () {
                        return {};
                    });

                    if (response.status === 422 && data.errors) {
                        if (data.errors.email) {
                            forgotEmail.classList.add(
                                'is-invalid'
                            );

                            forgotEmailError.textContent =
                                data.errors.email[0];

                            forgotEmail.focus();
                        }

                        return;
                    }

                    if (!response.ok || data.success === false) {
                        window.showToast(
                            'error',
                            data.mensaje ||
                            'No se pudo enviar el correo de recuperación.'
                        );

                        return;
                    }

                    window.showToast(
                        'success',
                        data.mensaje ||
                        'Si el correo existe, recibirás las instrucciones para recuperar tu contraseña.'
                    );

                    const correoRecuperacion =
                        forgotEmail.value.trim();

                    const resetEmail =
                        document.getElementById(
                            'resetEmail'
                        );

                    if (resetEmail && correoRecuperacion) {
                        resetEmail.value =
                            correoRecuperacion;
                    }

                    localStorage.setItem(
                        PANEL_KEY,
                        'reset'
                    );

                    cambiarPanel('reset', true);

                    formForgot.reset();
                })
                .catch(function () {
                    window.showToast(
                        'error',
                        'No se pudo conectar con el servidor. Intenta de nuevo.'
                    );
                })
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });

        if (forgotEmail) {
            forgotEmail.addEventListener('input', function () {
                validarEnVivo(
                    forgotEmail,
                    forgotEmailError
                );
            });
        }
    }

    if (formResetPassword) {
        const resetEmail =
            document.getElementById('resetEmail');

        const resetPassword =
            document.getElementById('resetPassword');

        const resetPasswordConfirmation =
            document.getElementById(
                'resetPasswordConfirmation'
            );

        const resetEmailError =
            document.getElementById(
                'reset-email-error'
            );

        const resetPasswordError =
            document.getElementById(
                'reset-password-error'
            );

        const resetPasswordConfirmationError =
            document.getElementById(
                'reset-password-confirmation-error'
            );

        function mostrarErroresReset(errors) {
            let primerCampoInvalido = null;

            if (errors.email) {
                resetEmail.classList.add(
                    'is-invalid'
                );

                resetEmailError.textContent =
                    errors.email[0];

                primerCampoInvalido =
                    primerCampoInvalido ||
                    resetEmail;
            }

            if (errors.password) {
                resetPassword.classList.add(
                    'is-invalid'
                );

                resetPasswordError.textContent =
                    errors.password[0];

                primerCampoInvalido =
                    primerCampoInvalido ||
                    resetPassword;
            }

            if (errors.password_confirmation) {
                resetPasswordConfirmation.classList.add(
                    'is-invalid'
                );

                resetPasswordConfirmationError.textContent =
                    errors.password_confirmation[0];

                primerCampoInvalido =
                    primerCampoInvalido ||
                    resetPasswordConfirmation;
            }

            if (primerCampoInvalido) {
                primerCampoInvalido.focus();
            }
        }

        function validarConfirmacionPassword() {
            if (!resetPasswordConfirmation) {
                return;
            }

            const tieneValor =
                resetPasswordConfirmation.value.trim() !== '';

            const coincide =
                resetPasswordConfirmation.value ===
                resetPassword.value;

            if (!tieneValor) {
                resetPasswordConfirmation.classList.remove(
                    'is-invalid',
                    'is-valid'
                );

                resetPasswordConfirmationError.textContent = '';

                return;
            }

            if (coincide) {
                resetPasswordConfirmation.classList.remove(
                    'is-invalid'
                );

                resetPasswordConfirmation.classList.add(
                    'is-valid'
                );

                resetPasswordConfirmationError.textContent = '';
            } else {
                resetPasswordConfirmation.classList.remove(
                    'is-valid'
                );

                resetPasswordConfirmation.classList.add(
                    'is-invalid'
                );

                resetPasswordConfirmationError.textContent =
                    'Las contraseñas no coinciden.';
            }
        }

        if (resetPassword) {
            resetPassword.addEventListener(
                'input',
                function () {
                    validarEnVivo(
                        resetPassword,
                        resetPasswordError
                    );

                    validarConfirmacionPassword();
                }
            );
        }

        if (resetPasswordConfirmation) {
            resetPasswordConfirmation.addEventListener(
                'input',
                function () {
                    validarConfirmacionPassword();
                }
            );
        }

        formResetPassword.addEventListener(
            'submit',
            function (event) {
                event.preventDefault();

                limpiarErroresPanel(
                    obtenerPanel('reset')
                );

                validarConfirmacionPassword();

                const tokenInput =
                    document.getElementById(
                        'resetToken'
                    );

                if (
                    !tokenInput ||
                    !tokenInput.value.trim()
                ) {
                    window.showToast(
                        'error',
                        'Primero debes abrir el enlace que recibiste en tu correo para validar la recuperación de contraseña.'
                    );

                    return;
                }

                if (
                    !resetPassword.value.trim() ||
                    resetPassword.value.length < 8
                ) {
                    resetPassword.classList.add(
                        'is-invalid'
                    );

                    resetPasswordError.textContent =
                        'La contraseña debe tener al menos 8 caracteres.';

                    resetPassword.focus();

                    return;
                }

                if (
                    resetPassword.value !==
                    resetPasswordConfirmation.value
                ) {
                    resetPasswordConfirmation.classList.add(
                        'is-invalid'
                    );

                    resetPasswordConfirmationError.textContent =
                        'Las contraseñas no coinciden.';

                    resetPasswordConfirmation.focus();

                    return;
                }

                const botonSubmit =
                    formResetPassword.querySelector(
                        'button[type="submit"]'
                    );

                const textoOriginal =
                    botonSubmit.innerHTML;

                botonSubmit.disabled = true;

                botonSubmit.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>Restableciendo...';

                const formData =
                    new FormData(formResetPassword);

                fetch(formResetPassword.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: formData,
                })
                    .then(async function (response) {
                        const data =
                            await response.json().catch(
                                function () {
                                    return {};
                                }
                            );

                        if (
                            response.status === 422 &&
                            data.errors
                        ) {
                            mostrarErroresReset(
                                data.errors
                            );

                            return;
                        }

                        if (
                            !response.ok ||
                            data.success === false
                        ) {
                            window.showToast(
                                'error',
                                data.mensaje ||
                                'No se pudo restablecer la contraseña.'
                            );

                            return;
                        }

                        localStorage.removeItem(PANEL_KEY);

                        window.showToast(
                            'success',
                            data.mensaje ||
                            'Contraseña restablecida correctamente. Ya puedes iniciar sesión.'
                        );

                        setTimeout(function () {
                            window.location.href =
                                data.redirect ||
                                '/login';
                        }, 1000);
                    })
                    .catch(function () {
                        window.showToast(
                            'error',
                            'No se pudo conectar con el servidor. Intenta de nuevo.'
                        );
                    })
                    .finally(function () {
                        botonSubmit.disabled = false;
                        botonSubmit.innerHTML =
                            textoOriginal;
                    });
            }
        );
    }
});
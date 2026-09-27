document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    const formLogin = document.getElementById('formLogin');

    if (!formLogin) {
        return;
    }

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    const emailError = document.getElementById('email-error');
    const passwordError = document.getElementById('password-error');
    const rememberInput = document.getElementById('remember');

    const REMEMBER_KEY = 'loginRememberedEmail';
    
    const emailGuardado = localStorage.getItem(REMEMBER_KEY);

    if (emailGuardado && rememberInput) {
        emailInput.value = emailGuardado;
        rememberInput.checked = true;
    }

    function limpiarErrores() {
        emailInput.classList.remove('is-invalid', 'is-valid');
        passwordInput.classList.remove('is-invalid', 'is-valid');
        emailError.textContent = '';
        passwordError.textContent = '';
    }

    function mostrarErroresDeCampos(errors) {
        let primerCampoInvalido = null;

        if (errors.email) {
            emailInput.classList.remove('is-valid');
            emailInput.classList.add('is-invalid');
            emailError.textContent = errors.email[0];
            primerCampoInvalido = primerCampoInvalido || emailInput;
        }

        if (errors.password) {
            passwordInput.classList.remove('is-valid');
            passwordInput.classList.add('is-invalid');
            passwordError.textContent = errors.password[0];
            primerCampoInvalido = primerCampoInvalido || passwordInput;
        }

        if (primerCampoInvalido) {
            primerCampoInvalido.focus();
        }
    }

    function validarEnVivo(input, errorDiv) {
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

    emailInput.addEventListener('input', function () {
        validarEnVivo(emailInput, emailError);
    });

    passwordInput.addEventListener('input', function () {
        validarEnVivo(passwordInput, passwordError);
    });

    const togglePasswordBtn = document.getElementById('togglePassword');

    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', function () {
            const icon = togglePasswordBtn.querySelector('i');
            const seVaAMostrar = passwordInput.type === 'password';

            passwordInput.type = seVaAMostrar ? 'text' : 'password';

            icon.classList.toggle('fa-eye', !seVaAMostrar);
            icon.classList.toggle('fa-eye-slash', seVaAMostrar);

            togglePasswordBtn.setAttribute(
                'aria-label',
                seVaAMostrar ? 'Ocultar contraseña' : 'Mostrar contraseña'
            );
        });
    }

    formLogin.addEventListener('submit', function (event) {
        event.preventDefault();

        limpiarErrores();

        const botonSubmit = formLogin.querySelector('button[type="submit"]');
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
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));

                if (response.status === 422 && data.errors) {
                    mostrarErroresDeCampos(data.errors);
                    return;
                }
                
                if (!response.ok || data.success === false) {
                    window.showToast(
                        'error',
                        data.mensaje || 'Ocurrió un error al iniciar sesión.'
                    );
                    emailInput.focus();
                    return;
                }

                if (rememberInput && rememberInput.checked) {
                    localStorage.setItem(REMEMBER_KEY, emailInput.value.trim());
                } else {
                    localStorage.removeItem(REMEMBER_KEY);
                }

                setTimeout(function () {
                    window.location.href = data.redirect || '/dashboard';
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
});
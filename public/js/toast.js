/**
 * Sistema global de alertas flotantes (toasts).
 *
 * Uso:
 *   window.showToast('success', 'Usuario creado correctamente.');
 *
 *   window.showToast(
 *       'error',
 *       ['El correo ya existe.', 'La contraseña es muy corta.'],
 *       {
 *           title: 'Hay errores en el formulario',
 *           duration: 6000
 *       }
 *   );
 *
 * Tipos:
 *   success, error, warning, info
 *
 * `message` puede ser un string o un arreglo de strings.
 */
(function () {

    var STACK_ID = 'toastStack';
    var DEFAULT_DURATION = 6000;

    var ICONS = {
        success: 'fa-solid fa-circle-check',
        error: 'fa-solid fa-circle-exclamation',
        warning: 'fa-solid fa-triangle-exclamation',
        info: 'fa-solid fa-circle-info'
    };

    function getStack() {
        return document.getElementById(STACK_ID);
    }

    function buildBody(message) {

        if (Array.isArray(message)) {

            var ul = document.createElement('ul');
            ul.className = 'toast-list';

            message.forEach(function (line) {

                var li = document.createElement('li');
                li.textContent = line;

                ul.appendChild(li);
            });

            return ul;
        }

        var div = document.createElement('div');
        div.className = 'toast-message';
        div.textContent = message;

        return div;
    }

    function showToast(type, message, options) {

        options = options || {};

        var stack = getStack();
        var isEmptyList =
            Array.isArray(message) &&
            message.length === 0;

        if (!stack || !message || isEmptyList) {
            return;
        }

        var toastType = ICONS[type] ? type : 'info';

        var toast = document.createElement('div');

        toast.className =
            'toast-item toast-' + toastType;

        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');

        /* ---------------------------------- */
        /* Icono                              */
        /* ---------------------------------- */

        var icon = document.createElement('div');

        icon.className = 'toast-icon';

        icon.innerHTML =
            '<i class="' +
            (ICONS[type] || ICONS.info) +
            '"></i>';

        /* ---------------------------------- */
        /* Contenido                          */
        /* ---------------------------------- */

        var content = document.createElement('div');

        content.className = 'toast-content';

        if (options.title) {

            var title = document.createElement('div');

            title.className = 'toast-title';
            title.textContent = options.title;

            content.appendChild(title);
        }

        content.appendChild(buildBody(message));

        /* ---------------------------------- */
        /* Botón cerrar                       */
        /* ---------------------------------- */

        var closeBtn = document.createElement('button');

        closeBtn.type = 'button';
        closeBtn.className = 'toast-close';

        closeBtn.setAttribute(
            'aria-label',
            'Cerrar'
        );

        closeBtn.innerHTML =
            '<i class="fa-solid fa-xmark"></i>';

        /* ---------------------------------- */
        /* Barra de progreso                  */
        /* ---------------------------------- */

        var progress = document.createElement('div');

        progress.className = 'toast-progress';

        /* ---------------------------------- */
        /* Construcción                       */
        /* ---------------------------------- */

        toast.appendChild(icon);
        toast.appendChild(content);
        toast.appendChild(closeBtn);
        toast.appendChild(progress);

        stack.appendChild(toast);

        /* ---------------------------------- */
        /* Estado                             */
        /* ---------------------------------- */

        var duration =
            Number(options.duration) > 0
                ? Number(options.duration)
                : DEFAULT_DURATION;

        var dismissed = false;
        var paused = false;

        var elapsed = 0;
        var lastTime = null;
        var animationFrame = null;

        /* ---------------------------------- */
        /* Entrada animada                    */
        /* ---------------------------------- */

        toast.style.opacity = '0';
        toast.style.transform = 'translateX(24px)';

        icon.style.opacity = '0';
        icon.style.transform =
            'scale(.65) rotate(-8deg)';

        requestAnimationFrame(function () {

            toast.style.transition =
                'opacity .25s ease, transform .25s ease';

            icon.style.transition =
                'opacity .45s cubic-bezier(.34,1.56,.64,1), ' +
                'transform .45s cubic-bezier(.34,1.56,.64,1)';

            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';

            icon.style.opacity = '1';
            icon.style.transform =
                'scale(1) rotate(0)';
        });

        /* ---------------------------------- */
        /* Barra inicial                      */
        /* ---------------------------------- */

        progress.style.transform = 'scaleX(1)';
        progress.style.transformOrigin = 'left center';

        /* ---------------------------------- */
        /* Animación principal                */
        /* ---------------------------------- */

        function updateProgress(timestamp) {

            if (dismissed) {
                return;
            }

            if (lastTime === null) {
                lastTime = timestamp;
            }

            var delta = timestamp - lastTime;

            lastTime = timestamp;

            if (!paused) {

                elapsed += delta;

                var percentage =
                    Math.max(
                        0,
                        1 - (elapsed / duration)
                    );

                progress.style.transform =
                    'scaleX(' + percentage + ')';

                if (elapsed >= duration) {

                    progress.style.transform =
                        'scaleX(0)';

                    dismiss();

                    return;
                }
            }

            animationFrame =
                requestAnimationFrame(updateProgress);
        }

        animationFrame =
            requestAnimationFrame(updateProgress);

        /* ---------------------------------- */
        /* Pausar                             */
        /* ---------------------------------- */

        function pauseToast() {

            if (dismissed || paused) {
                return;
            }

            paused = true;

            /*
             * Importante:
             * reiniciamos lastTime para que el tiempo
             * pasado mientras estaba pausado no se
             * contabilice al regresar.
             */
            lastTime = null;
        }

        /* ---------------------------------- */
        /* Reanudar                           */
        /* ---------------------------------- */

        function resumeToast() {

            if (dismissed || !paused) {
                return;
            }

            paused = false;

            /*
             * Evita que el primer frame después
             * del hover contabilice todo el tiempo
             * que estuvo detenido.
             */
            lastTime = null;
        }

        /* ---------------------------------- */
        /* Cerrar                             */
        /* ---------------------------------- */

        function dismiss() {

            if (dismissed) {
                return;
            }

            dismissed = true;

            if (animationFrame !== null) {

                cancelAnimationFrame(
                    animationFrame
                );

                animationFrame = null;
            }

            toast.style.transition =
                'opacity .25s ease, transform .25s ease';

            toast.style.opacity = '0';
            toast.style.transform =
                'translateX(24px)';

            toast.addEventListener(
                'transitionend',
                function (event) {

                    if (event.propertyName !== 'opacity') {
                        return;
                    }

                    toast.remove();

                },
                { once: true }
            );
        }

        /* ---------------------------------- */
        /* Eventos                            */
        /* ---------------------------------- */

        toast.addEventListener(
            'mouseenter',
            pauseToast
        );

        toast.addEventListener(
            'mouseleave',
            resumeToast
        );

        closeBtn.addEventListener(
            'click',
            function () {

                dismiss();
            }
        );
    }

    window.showToast = showToast;

})();
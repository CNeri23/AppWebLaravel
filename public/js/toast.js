window.showToast = function (tipo, mensaje) {
    const tiposValidos = ['success', 'error', 'warning', 'info'];

    if (!tiposValidos.includes(tipo)) {
        tipo = 'info';
    }

    const temaOscuro =
        document.documentElement.getAttribute('data-bs-theme') === 'dark';

    Swal.fire({
        toast: true,
        position: 'bottom-end',

        // Icono nativo de SweetAlert2 (con sus animaciones).
        // OJO: no uses iconHtml; reemplaza el icono nativo por un <i> de
        // Font Awesome y se pierden las animaciones.
        icon: tipo,
        title: mensaje,

        showConfirmButton: false,
        showCloseButton: true,

        timer: 4500,
        timerProgressBar: true,

        customClass: {
            popup: `glass-toast glass-toast-${tipo} ${temaOscuro ? 'swal-toast-dark' : 'swal-toast-light'}`,
            icon: 'glass-toast-icon',
            title: 'glass-toast-title',
            closeButton: 'swal-toast-close',
            timerProgressBar: 'swal-toast-progress'
        },

        // Al personalizar showClass hay que conservar la clave "icon":
        // sin ella SweetAlert no dispara la animación del icono.
        showClass: {
            popup: 'glass-toast-show',
            icon: 'swal2-icon-show'
        },

        hideClass: {
            popup: 'glass-toast-hide'
        },

        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
};
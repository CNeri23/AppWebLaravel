window.showToast = function (tipo, mensaje) {
    const tiposValidos = ['success', 'error', 'warning', 'info'];

    if (!tiposValidos.includes(tipo)) {
        tipo = 'info';
    }

    const iconos = {
        success: 'fa-solid fa-check',
        error: 'fa-solid fa-xmark',
        warning: 'fa-solid fa-exclamation',
        info: 'fa-solid fa-info'
    };

    const temaOscuro =
        document.documentElement.getAttribute('data-bs-theme') === 'dark';

    Swal.fire({
        toast: true,
        position: 'bottom-end',

        icon: tipo,
        iconHtml: `<i class="${iconos[tipo]}"></i>`,
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

        showClass: {
            popup: 'glass-toast-show'
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
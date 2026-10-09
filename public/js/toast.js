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
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 4500,
        timerProgressBar: true,
        customClass: {
            popup: `toast toast-${tipo}`,
            icon: 'toast-icon',
            title: 'toast-title',
            closeButton: 'swal-toast-close',
            timerProgressBar: 'swal-toast-progress'
        },

        showClass: {
            popup: 'toast-show',
            icon: 'swal2-icon-show'
        },

        hideClass: {
            popup: 'toast-hide'
        },

        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
};
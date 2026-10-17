window.showToast = function (tipo, mensaje) {
    const tiposValidos = ['success', 'error', 'warning', 'info'];

    if (!tiposValidos.includes(tipo)) {
        tipo = 'info';
    }

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        showCloseButton: true,
        timer: 4500,
        timerProgressBar: true,
        showClass: { popup: 'toast-anim-in' },
        hideClass: { popup: 'toast-anim-out' },
        customClass: {
            popup: `toast toast-${tipo}`,
            icon: 'toast-icon',
            title: 'toast-title',
            closeButton: 'swal-toast-close',
            timerProgressBar: 'swal-toast-progress'
        },

        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
};
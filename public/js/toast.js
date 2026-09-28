window.showToast = function (tipo, mensaje) {
    const temaOscuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';

    const colores = {
        success: temaOscuro ? '#4ade80' : '#16a34a',
        error: temaOscuro ? '#f87171' : '#dc3545',
        warning: temaOscuro ? '#fbbf24' : '#d97706',
        info: temaOscuro ? '#60a5fa' : '#2563eb'
    };

    Swal.fire({
        toast: true,
        position: 'bottom-end',
        icon: tipo,
        title: mensaje,

        showConfirmButton: false,
        showCloseButton: true,

        timer: 4000,
        timerProgressBar: true,
        timerProgressBar: true,

        background: temaOscuro ? '#111827' : '#ffffff',
        color: temaOscuro ? '#f8fafc' : '#1f2937',

        iconColor: colores[tipo] || colores.info,

        customClass: {
            popup: temaOscuro ? 'swal-toast-dark' : 'swal-toast-light',
            closeButton: 'swal-toast-close',
            timerProgressBar: 'swal-toast-progress'
        },

        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
};
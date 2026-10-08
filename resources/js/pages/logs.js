document.addEventListener('DOMContentLoaded', function () {
    const tablaLogs = document.querySelector('#tablaLogs');

    if (tablaLogs) {
        new DataTable(tablaLogs, {
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'No hay logs registrados',

                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            },

            pageLength: 10,

            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],

            order: [
                [0, 'desc']
            ],

            columnDefs: [
                {
                    orderable: false,
                    targets: [4, 6]
                }
            ],

            layout: {
                topStart: 'pageLength',
                topEnd: 'search',
                bottomStart: 'info',
                bottomEnd: 'paging'
            },

            initComplete: function () {
                configurarTooltipsPaginacion();
            },

            drawCallback: function () {
                configurarTooltipsPaginacion();
            }
        });

        function configurarTooltipsPaginacion() {
            const paginacion = document.querySelector(
                '#tablaLogs_wrapper .dt-paging'
            );

            if (!paginacion) {
                return;
            }

            const botones = paginacion.querySelectorAll('button');

            botones.forEach(function (button) {
                const icono = button.querySelector('i');

                if (!icono) {
                    return;
                }

                if (icono.classList.contains('fa-angles-left')) {
                    button.setAttribute('title', 'Primera página');
                    button.setAttribute('aria-label', 'Primera página');
                }

                if (icono.classList.contains('fa-angle-left')) {
                    button.setAttribute('title', 'Página anterior');
                    button.setAttribute('aria-label', 'Página anterior');
                }

                if (icono.classList.contains('fa-angle-right')) {
                    button.setAttribute('title', 'Página siguiente');
                    button.setAttribute('aria-label', 'Página siguiente');
                }

                if (icono.classList.contains('fa-angles-right')) {
                    button.setAttribute('title', 'Última página');
                    button.setAttribute('aria-label', 'Última página');
                }
            });
        }
    }
});
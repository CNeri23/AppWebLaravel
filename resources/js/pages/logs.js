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
                    first: 'Primero',
                    previous: 'Anterior',
                    next: 'Siguiente',
                    last: 'Último'
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
            }
        });
    }
});
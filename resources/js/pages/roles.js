document.addEventListener('DOMContentLoaded', function () {
    const tablaRoles = document.querySelector('#tablaRoles');

    if (tablaRoles) {
        new DataTable(tablaRoles, {
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron roles',
                emptyTable: 'No hay roles registrados',

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
                [0, 'asc']
            ],
            columnDefs: [
                {
                    orderable: false,
                    searchable: false,
                    targets: 3
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

    const modalEditar = document.getElementById('modalEditarRol');

    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }
            const id = button.dataset.id;
            const name = button.dataset.name;
            const description = button.dataset.description;
            const url = button.dataset.url;

            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            document.getElementById('editar_description').value = description || '';
            document.getElementById('formEditarRol').setAttribute('action', url);
        });
    }

    const modalEliminar = document.getElementById('modalEliminarRol');

    if (modalEliminar) {
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }
            const name = button.dataset.name;
            const url = button.dataset.url;

            document.getElementById('eliminar_nombre').textContent = name;
            document.getElementById('formEliminarRol').setAttribute('action', url);
        });
    }
});
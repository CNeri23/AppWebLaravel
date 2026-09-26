document.addEventListener('DOMContentLoaded', function () {
    const tablaUsuarios = document.querySelector('#tablaUsuarios');

    if (tablaUsuarios) {
        new DataTable(tablaUsuarios, {
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron usuarios',
                emptyTable: 'No hay usuarios registrados',

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
                [1, 'asc']
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

    const modalEditar = document.getElementById('modalEditarUsuario');

    if (modalEditar) {
        modalEditar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const name = button.dataset.name;
            const email = button.dataset.email;
            const url = button.dataset.url;

            document.getElementById('editar_id').value = id;
            document.getElementById('editar_name').value = name;
            document.getElementById('editar_email').value = email;
            document.getElementById('formEditarUsuario').setAttribute('action', url);
        });
    }

    const modalPassword = document.getElementById('modalPasswordUsuario');

    if (modalPassword) {
        modalPassword.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const name = button.dataset.name;
            const url = button.dataset.url;

            document.getElementById('password_usuario_id').value = id;
            document.getElementById('password_usuario_nombre').textContent = name;
            document.getElementById('password_nueva').value = '';
            document.getElementById('password_nueva_confirmation').value = '';
            document.getElementById('formPasswordUsuario').setAttribute('action', url);
        });
    }

    const modalRoles = document.getElementById('modalRolesUsuario');

    if (modalRoles) {
        modalRoles.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }
            const id = button.dataset.id;
            const name = button.dataset.name;
            const roles = button.dataset.roles
                ? button.dataset.roles.split(',')
                : [];
            const url = button.dataset.url;

            document.getElementById('roles_usuario_id').value = id;
            document.getElementById('roles_usuario_nombre').textContent = name;
            document.querySelectorAll('.rol-usuario-checkbox').forEach(function (checkbox) {
                checkbox.checked = roles.includes(checkbox.value);
            });
            document.getElementById('formRolesUsuario').setAttribute('action', url);
        });
    }

    const modalEliminar = document.getElementById('modalEliminarUsuario');

    if (modalEliminar) {
        modalEliminar.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }
            const name = button.dataset.name;
            const url = button.dataset.url;

            document.getElementById('eliminar_nombre').textContent = name;
            document.getElementById('formEliminarUsuario').setAttribute('action', url);
        });
    }
});
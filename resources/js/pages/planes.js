document.addEventListener('DOMContentLoaded', function () {
    const tabla = document.getElementById('tablaPlanes');

    if (!tabla) {
        return;
    }

    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let dataTable = null;
    let planOriginalSnapshot = null;

    const modalNuevo = document.getElementById('modalNuevoPlan');
    const modalEditar = document.getElementById('modalEditarPlan');
    const modalEliminar = document.getElementById('modalEliminarPlan');

    const formNuevo = document.getElementById('formNuevoPlan');
    const formEditar = document.getElementById('formEditarPlan');
    const formEliminar = document.getElementById('formEliminarPlan');

    const acciones = Array.isArray(window.accionesPlanes)
        ? window.accionesPlanes
        : [];

    function tieneAccion(slug) {
        return acciones.some(function (accion) {
            return accion.slug === slug;
        });
    }

    function escapeHtml(valor) {
        const div =
            document.createElement('div');

        div.textContent =
            valor ?? '';

        return div.innerHTML;
    }

    function escapeAttribute(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function obtenerIcono(slug, iconoDefault) {
        const accion =
            acciones.find(function (item) {
                return item.slug === slug;
            });

        return accion && accion.icono
            ? accion.icono
            : iconoDefault;
    }

    function obtenerNombre(slug, nombreDefault) {
        const accion =
            acciones.find(function (item) {
                return item.slug === slug;
            });

        return accion && accion.nombre
            ? accion.nombre
            : nombreDefault;
    }

    // Los precios se guardan en MXN; aquí se convierten a la moneda configurada.
    function convertirPrecio(precio) {
        const tasa = Number((window.monedaPlanes || {}).tasa) || 1;
        const numero = Number.isFinite(Number(precio)) ? Number(precio) : 0;

        return Math.round(numero * tasa * 100) / 100;
    }

    function formatearPrecio(precio) {
        const moneda = window.monedaPlanes || { codigo: 'MXN', simbolo: '$' };
        const numero = convertirPrecio(precio);

        return moneda.simbolo + numero.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatearDuracion(dias) {
        const numero = Number(dias);

        return numero + ' ' + (numero === 1 ? 'día' : 'días');
    }

    function crearBotonesAcciones(plan) {
        let html =
            '<div class="plan-actions">';

        const acciones =
            window.accionesPlanes || [];

        acciones.forEach(function (accion) {

            if (accion.slug === 'planes.editar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-outline-primary plan-action-btn btn-editar-plan" ' +
                    'data-id="' + escapeAttribute(plan.id) + '" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-tooltip="' + escapeAttribute(accion.nombre) + '">' +
                    (accion.icono ||
                        '<i class="fa-regular fa-pen-to-square"></i>') +
                    '</button>';
            }

            else if (accion.slug === 'planes.estado') {

                const activo =
                    Boolean(plan.activo);

                html +=
                    '<button type="button" ' +
                    'class="plan-toggle-btn ' +
                    (activo ? 'activo' : 'inactivo') +
                    '" ' +
                    'data-id="' + escapeAttribute(plan.id) + '" ' +
                    'data-activo="' + (activo ? '1' : '0') + '" ' +
                    'aria-pressed="' + (activo ? 'true' : 'false') + '" ' +
                    'title="' + escapeAttribute(
                        activo
                            ? 'Desactivar plan'
                            : 'Activar plan'
                    ) + '" ' +
                    'data-tooltip="' + escapeAttribute(
                        activo
                            ? 'Desactivar plan'
                            : 'Activar plan'
                    ) + '">' +
                    '<span class="plan-toggle-track">' +
                    '<span class="plan-toggle-thumb"></span>' +
                    '</span>' +
                    '</button>';
            }

            else if (accion.slug === 'planes.eliminar') {

                html +=
                    '<button type="button" ' +
                    'class="btn btn-outline-danger plan-action-btn btn-eliminar-plan" ' +
                    'data-id="' + escapeAttribute(plan.id) + '" ' +
                    'data-name="' + escapeAttribute(plan.nombre) + '" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-tooltip="' + escapeAttribute(accion.nombre) + '">' +
                    (accion.icono ||
                        '<i class="fa-regular fa-trash-can"></i>') +
                    '</button>';
            }

        });

        html +=
            '</div>';

        return html;
    }

    function crearEstado(plan) {
        return `
        <span class="plan-estado-text ${plan.activo ? 'activo' : 'inactivo'}">
            ${plan.activo ? 'Activo' : 'Inactivo'}
        </span>
    `;
    }

    function crearFilaPlan(plan, urls = null) {
        const fila =
            document.createElement('tr');

        const descripcion =
            plan.descripcion
                ? escapeHtml(plan.descripcion)
                : '<span class="text-secondary">Sin descripción</span>';

        fila.dataset.id =
            plan.id;

        fila.dataset.nombre =
            plan.nombre || '';

        fila.dataset.descripcion =
            plan.descripcion || '';

        fila.dataset.duracionDias =
            plan.duracion_dias;

        fila.dataset.precio =
            plan.precio;

        fila.dataset.activo =
            plan.activo ? '1' : '0';

        fila.innerHTML = `
            <td>
                <span class="fw-semibold plan-nombre">
                    ${escapeHtml(plan.nombre)}
                </span>
            </td>

            <td>
                <span class="text-secondary plan-descripcion">
                    ${descripcion}
                </span>
            </td>

            <td>
                <span class="plan-duracion">
                    ${escapeHtml(formatearDuracion(plan.duracion_dias))}
                </span>
            </td>

            <td>
                <span class="fw-semibold plan-precio">
                    ${escapeHtml(formatearPrecio(plan.precio))}
                </span>
            </td>

            <td class="text-center">
                ${crearEstado(plan)}
            </td>

            <td>
                ${crearBotonesAcciones(plan)}
            </td>
        `;

        return fila;
    }

    function obtenerDatosFila(fila) {
        return {
            id: fila.dataset.id,
            nombre: fila.dataset.nombre || '',
            descripcion: fila.dataset.descripcion || '',
            duracion_dias: fila.dataset.duracionDias || '',
            precio: fila.dataset.precio || '',
            activo: fila.dataset.activo === '1'
        };
    }

    function actualizarPlanEnTabla(plan) {
        if (!dataTable || !plan) {
            return;
        }

        dataTable
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (!fila) {
                    return;
                }

                if (
                    fila.dataset.id !==
                    String(plan.id)
                ) {
                    return;
                }

                const datos =
                    this.data();

                datos[0] =
                    '<span class="fw-semibold plan-nombre">' +
                    escapeHtml(plan.nombre) +
                    '</span>';

                datos[1] =
                    '<span class="text-secondary plan-descripcion">' +
                    (
                        plan.descripcion
                            ? escapeHtml(plan.descripcion)
                            : 'Sin descripción'
                    ) +
                    '</span>';

                datos[2] =
                    '<span class="plan-duracion">' +
                    escapeHtml(
                        formatearDuracion(
                            plan.duracion_dias
                        )
                    ) +
                    '</span>';

                datos[3] =
                    '<span class="fw-semibold plan-precio">' +
                    escapeHtml(
                        formatearPrecio(
                            plan.precio
                        )
                    ) +
                    '</span>';

                datos[4] =
                    crearEstado(plan);

                datos[5] =
                    crearBotonesAcciones(plan);

                this.data(
                    datos
                );

                fila.dataset.id =
                    plan.id;

                fila.dataset.nombre =
                    plan.nombre || '';

                fila.dataset.descripcion =
                    plan.descripcion || '';

                fila.dataset.duracionDias =
                    plan.duracion_dias;

                fila.dataset.precio =
                    plan.precio;

                fila.dataset.activo =
                    plan.activo ? '1' : '0';
            });

        dataTable.draw(false);
    }

    function eliminarFilaPorId(id) {
        if (!dataTable) {
            return;
        }

        dataTable
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (!fila) {
                    return;
                }

                if (
                    fila.dataset.id ===
                    String(id)
                ) {
                    this.remove();
                }
            });

        dataTable.draw(false);
    }

    function quitarFilaVacia() {
        const filaVacia =
            tabla.querySelector(
                'tbody .empty-row'
            );

        if (filaVacia) {
            filaVacia.remove();
        }
    }

    function mostrarFilaVacia() {
        if (!dataTable) {
            return;
        }

        if (dataTable.rows().count() > 0) {
            return;
        }

        const tbody =
            tabla.querySelector('tbody');

        tbody.innerHTML = `
        <tr class="empty-row">
            <td colspan="6" class="text-center py-5">
                <div class="text-secondary">
                    <i class="fa-solid fa-tags fa-2x mb-3"></i>
                    <div class="fw-semibold">No hay planes registrados.</div>
                </div>
            </td>
        </tr>
    `;
    }

    function agregarPlanATabla(plan, urls = null) {
        if (!dataTable) {
            return;
        }

        quitarFilaVacia();

        const fila =
            crearFilaPlan(
                plan,
                urls
            );

        dataTable.row
            .add(fila)
            .draw(false);
    }

    function buscarFilaPorId(id) {
        if (!dataTable) {
            return null;
        }

        let filaEncontrada = null;

        dataTable
            .rows()
            .every(function () {

                const fila =
                    this.node();

                if (
                    fila &&
                    fila.dataset.id ===
                    String(id)
                ) {
                    filaEncontrada =
                        fila;
                }
            });

        return filaEncontrada;
    }

    function peticion(url, method, body = null) {
        const opciones = {
            method,
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        };

        if (body instanceof FormData) {
            opciones.body =
                body;
        } else if (body) {
            opciones.headers['Content-Type'] =
                'application/json';

            opciones.body =
                body;
        }

        return fetch(
            url,
            opciones
        ).then(async function (response) {

            const data =
                await response
                    .json()
                    .catch(() => ({}));

            if (
                !response.ok ||
                data.success === false
            ) {
                if (data.errors) {
                    const primerError =
                        Object.values(
                            data.errors
                        ).flat()[0];

                    throw new Error(
                        primerError ||
                        data.mensaje ||
                        data.message ||
                        'Ocurrió un error al procesar la solicitud.'
                    );
                }

                throw new Error(
                    data.mensaje ||
                    data.message ||
                    'Ocurrió un error al procesar la solicitud.'
                );
            }

            return data;
        });
    }

    function cerrarModal(modal) {
        if (modal) {
            const instancia =
                bootstrap.Modal.getInstance(
                    modal
                );

            if (instancia) {
                instancia.hide();
            }
        }
    }

    function resetFormulario(formulario) {
        if (!formulario) {
            return;
        }

        formulario.reset();

        formulario
            .querySelectorAll(
                '.is-valid, .is-invalid'
            )
            .forEach(function (elemento) {
                elemento.classList.remove(
                    'is-valid',
                    'is-invalid'
                );
            });
    }

    function mostrarError(error) {
        window.showToast(
            'error',
            error.message
        );
    }

    function mostrarExito(mensaje) {
        window.showToast(
            'success',
            mensaje
        );
    }

    function inicializarDataTable() {
        if (
            !window.DataTable &&
            !window.jQuery
        ) {
            return;
        }

        if (window.DataTable) {
            dataTable =
                new DataTable(
                    '#tablaPlanes',
                    {
                        autoWidth: false,

                        language: {
                            search: 'Buscar:',
                            lengthMenu: 'Mostrar _MENU_ registros',
                            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                            infoFiltered: '(filtrado de _MAX_ registros)',
                            zeroRecords: 'No se encontraron planes',
                            emptyTable: 'No hay planes registrados',

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
                                targets: 5,
                                width: '20%'
                            }
                        ],

                        layout: {
                            topStart: 'pageLength',
                            topEnd: 'search',
                            bottomStart: 'info',
                            bottomEnd: 'paging'
                        }
                    }
                );

            return;
        }

        if (
            window.jQuery &&
            $.fn.DataTable
        ) {
            dataTable =
                $('#tablaPlanes').DataTable({
                    autoWidth: false,

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
                            targets: 5,
                            width: '20%'
                        }
                    ],

                    language: {
                        search: 'Buscar:',
                        lengthMenu: 'Mostrar _MENU_ registros',
                        info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                        infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                        zeroRecords: 'No se encontraron planes',
                        emptyTable: 'No hay planes registrados',

                        paginate: {
                            first: 'Primero',
                            previous: 'Anterior',
                            next: 'Siguiente',
                            last: 'Último'
                        }
                    }
                });
        }
    }

    function abrirModalEditar(fila) {
        if (!fila || !formEditar) {
            return;
        }

        const datos =
            obtenerDatosFila(fila);

        planOriginalSnapshot = {
            nombre: datos.nombre,
            descripcion: datos.descripcion,
            duracion_dias:
                String(datos.duracion_dias),
            precio:
                convertirPrecio(
                    datos.precio
                ).toFixed(2)
        };

        formEditar.action =
            '/planes/' +
            encodeURIComponent(
                datos.id
            );

        document.getElementById(
            'editarPlanNombre'
        ).value =
            datos.nombre;

        document.getElementById(
            'editarPlanDescripcion'
        ).value =
            datos.descripcion;

        document.getElementById(
            'editarPlanDuracion'
        ).value =
            datos.duracion_dias;

        document.getElementById(
            'editarPlanPrecio'
        ).value =
            convertirPrecio(
                datos.precio
            ).toFixed(2);
    }

    formNuevo?.addEventListener(
        'submit',
        function (evento) {
            evento.preventDefault();

            const boton =
                formNuevo.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal =
                boton?.innerHTML;

            if (boton) {
                boton.disabled =
                    true;

                boton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>' +
                    'Guardando...';
            }

            const formData =
                new FormData(
                    formNuevo
                );

            peticion(
                formNuevo.action,
                'POST',
                formData
            )
                .then(function (data) {

                    cerrarModal(
                        modalNuevo
                    );

                    mostrarExito(
                        data.mensaje ||
                        'Plan creado correctamente.'
                    );

                    agregarPlanATabla(
                        data.plan,
                        data.urls
                    );

                    formNuevo.reset();
                })
                .catch(
                    mostrarError
                )
                .finally(function () {

                    if (boton) {
                        boton.disabled =
                            false;

                        boton.innerHTML =
                            textoOriginal;
                    }
                });
        }
    );

    formEditar?.addEventListener(
        'submit',
        function (evento) {
            evento.preventDefault();

            const formData =
                new FormData(
                    formEditar
                );

            const datosActuales = {
                nombre:
                    String(
                        formData.get(
                            'nombre'
                        ) || ''
                    ).trim(),

                descripcion:
                    String(
                        formData.get(
                            'descripcion'
                        ) || ''
                    ).trim(),

                duracion_dias:
                    String(
                        formData.get(
                            'duracion_dias'
                        ) || ''
                    ).trim(),

                precio:
                    Number(
                        formData.get(
                            'precio'
                        ) || 0
                    ).toFixed(2)
            };

            const snapshot =
                planOriginalSnapshot ||
                {};

            const hayCambios =
                datosActuales.nombre !==
                snapshot.nombre ||
                datosActuales.descripcion !==
                snapshot.descripcion ||
                datosActuales.duracion_dias !==
                snapshot.duracion_dias ||
                datosActuales.precio !==
                snapshot.precio;

            if (!hayCambios) {
                cerrarModal(
                    modalEditar
                );

                window.showToast(
                    'info',
                    'No hubo cambios para actualizar.'
                );

                return;
            }

            const boton =
                formEditar.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal =
                boton?.innerHTML;

            if (boton) {
                boton.disabled =
                    true;

                boton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>' +
                    'Guardando...';
            }

            peticion(
                formEditar.action,
                'POST',
                formData
            )
                .then(function (data) {

                    actualizarPlanEnTabla(
                        data.plan
                    );

                    cerrarModal(
                        modalEditar
                    );

                    mostrarExito(
                        data.mensaje ||
                        'Plan actualizado correctamente.'
                    );
                })
                .catch(
                    mostrarError
                )
                .finally(function () {

                    if (boton) {
                        boton.disabled =
                            false;

                        boton.innerHTML =
                            textoOriginal;
                    }
                });
        }
    );

    formEliminar?.addEventListener(
        'submit',
        function (evento) {
            evento.preventDefault();

            const boton =
                formEliminar.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal =
                boton?.innerHTML;

            if (boton) {
                boton.disabled =
                    true;

                boton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>' +
                    'Eliminando...';
            }

            const formData =
                new FormData(
                    formEliminar
                );

            peticion(
                formEliminar.action,
                'POST',
                formData
            )
                .then(function (data) {

                    const id =
                        data.id ||
                        formEliminar.querySelector(
                            'input[name="id"]'
                        )?.value;

                    eliminarFilaPorId(
                        id
                    );

                    cerrarModal(
                        modalEliminar
                    );

                    mostrarFilaVacia();

                    mostrarExito(
                        data.mensaje ||
                        'Plan eliminado correctamente.'
                    );
                })
                .catch(
                    mostrarError
                )
                .finally(function () {

                    if (boton) {
                        boton.disabled =
                            false;

                        boton.innerHTML =
                            textoOriginal;
                    }
                });
        }
    );

    tabla.addEventListener(
        'click',
        function (evento) {

            const toggle =
                evento.target.closest(
                    '.plan-toggle-btn'
                );

            const botonEditar =
                evento.target.closest(
                    '.btn-editar-plan'
                );

            const botonEliminar =
                evento.target.closest(
                    '.btn-eliminar-plan'
                );

            if (toggle) {
                const id =
                    toggle.dataset.id;

                if (
                    !id ||
                    toggle.disabled
                ) {
                    return;
                }

                toggle.disabled =
                    true;

                toggle.classList.add(
                    'procesando'
                );

                peticion(
                    '/planes/' +
                    encodeURIComponent(id) +
                    '/toggle',
                    'POST',
                    new FormData()
                )
                    .then(function (data) {

                        actualizarPlanEnTabla(
                            data.plan
                        );

                        mostrarExito(
                            data.mensaje ||
                            'Estado del plan actualizado correctamente.'
                        );
                    })
                    .catch(
                        mostrarError
                    )
                    .finally(function () {

                        const fila =
                            buscarFilaPorId(
                                id
                            );

                        if (fila) {
                            const toggleActual =
                                fila.querySelector(
                                    '.plan-toggle-btn'
                                );

                            if (toggleActual) {
                                toggleActual.disabled =
                                    false;

                                toggleActual.classList.remove(
                                    'procesando'
                                );
                            }
                        }
                    });

                return;
            }

            if (botonEditar) {
                const fila =
                    botonEditar.closest(
                        'tr'
                    );

                abrirModalEditar(
                    fila
                );

                if (modalEditar) {
                    bootstrap.Modal.getOrCreateInstance(
                        modalEditar
                    ).show();
                }

                return;
            }

            if (botonEliminar) {
                const id =
                    botonEliminar.dataset.id;

                const nombre =
                    botonEliminar.dataset.name ||
                    '';

                if (!formEliminar) {
                    return;
                }

                formEliminar.action =
                    '/planes/' +
                    encodeURIComponent(
                        id
                    );

                const nombreElemento =
                    document.getElementById(
                        'nombrePlanEliminar'
                    );

                if (nombreElemento) {
                    nombreElemento.textContent =
                        nombre;
                }

                const inputId =
                    formEliminar.querySelector(
                        'input[name="id"]'
                    );

                if (inputId) {
                    inputId.value =
                        id;
                }

                if (modalEliminar) {
                    bootstrap.Modal.getOrCreateInstance(
                        modalEliminar
                    ).show();
                }
            }
        }
    );

    modalNuevo?.addEventListener(
        'hidden.bs.modal',
        function () {
            resetFormulario(
                formNuevo
            );
        }
    );

    modalEditar?.addEventListener(
        'hidden.bs.modal',
        function () {
            planOriginalSnapshot =
                null;
        }
    );

    inicializarDataTable();
});
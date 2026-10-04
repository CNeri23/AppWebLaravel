document.addEventListener('DOMContentLoaded', function () {
    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let tablaSesiones = null;

    const moneda = window.codigoMonedaSesiones || '';

    const modalAbrirSesionEl = document.getElementById('modalAbrirSesion');
    const modalDetalleSesionEl = document.getElementById('modalDetalleSesion');

    const modalAbrirSesion = modalAbrirSesionEl
        ? new bootstrap.Modal(modalAbrirSesionEl)
        : null;

    const formAbrirSesion = document.getElementById('formAbrirSesion');

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
            opciones.body = body;
        } else if (body) {
            opciones.headers['Content-Type'] = 'application/json';
            opciones.body = body;
        }

        return fetch(url, opciones).then(async (response) => {
            const data = await response.json().catch(() => ({}));

            if (!response.ok || data.success === false) {
                if (data.errors) {
                    const primerError = Object.values(data.errors).flat()[0];

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

    function mostrarError(error) {
        window.showToast(
            'error',
            error.message || 'Ocurrió un error al procesar la solicitud.'
        );
    }

    function escapeHtml(valor) {
        const div = document.createElement('div');

        div.textContent = valor ?? '';

        return div.innerHTML;
    }

    function escapeAttribute(valor) {
        return String(valor ?? '')
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function formatoMonto(valor) {
        if (valor === null || valor === undefined || valor === '') {
            return '—';
        }

        return Number(valor).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + ' ' + moneda;
    }

    function obtenerAccionesCajas() {
        return Array.isArray(window.accionesCajas)
            ? window.accionesCajas
            : [];
    }

    function badgeEstado(estado) {
        return estado === 'abierta'
            ? '<span class="badge text-bg-primary">Abierta</span>'
            : '<span class="badge text-bg-secondary">Cerrada</span>';
    }

    function crearAccionesSesion(sesion) {
        let html = '<div class="sesion-actions">';

        obtenerAccionesCajas().forEach(function (accion) {
            if (accion.slug === 'cajas.ver') {
                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-info sesion-action-btn" ' +
                    'title="' + escapeAttribute(accion.nombre) + '" ' +
                    'data-bs-toggle="modal" ' +
                    'data-bs-target="#modalDetalleSesion" ' +
                    'data-id="' + escapeAttribute(sesion.id) + '" ' +
                    'data-url="/sesiones-caja/' + escapeAttribute(sesion.id) + '">' +
                    (accion.icono || '<i class="fa-regular fa-eye"></i>') +
                    '</button>';
            }
        });

        if (sesion.estado === 'abierta') {
            obtenerAccionesCajas().forEach(function (accion) {
                if (accion.slug === 'cajas.retirar') {
                    html +=
                        '<button type="button" ' +
                        'class="btn btn-sm btn-outline-warning sesion-action-btn" ' +
                        'title="' + escapeAttribute(accion.nombre) + '" ' +
                        'data-bs-toggle="modal" ' +
                        'data-bs-target="#modalRetiroSesion" ' +
                        'data-id="' + escapeAttribute(sesion.id) + '" ' +
                        'data-caja="' + escapeAttribute(sesion.caja_nombre) + '" ' +
                        'data-disponible="' + escapeAttribute(sesion.efectivo_disponible_mostrado) + '" ' +
                        'data-url="/sesiones-caja/' + escapeAttribute(sesion.id) + '/retiros">' +
                        (accion.icono || '<i class="fa-solid fa-hand-holding-dollar"></i>') +
                        '</button>';
                }
            });

            obtenerAccionesCajas().forEach(function (accion) {
                if (accion.slug === 'cajas.cerrar') {
                    html +=
                        '<button type="button" ' +
                        'class="btn btn-sm btn-outline-danger sesion-action-btn" ' +
                        'title="' + escapeAttribute(accion.nombre) + '" ' +
                        'data-bs-toggle="modal" ' +
                        'data-bs-target="#modalCerrarSesion" ' +
                        'data-id="' + escapeAttribute(sesion.id) + '" ' +
                        'data-caja="' + escapeAttribute(sesion.caja_nombre) + '" ' +
                        'data-url="/sesiones-caja/' + escapeAttribute(sesion.id) + '/cerrar" ' +
                        'data-arqueo-url="/sesiones-caja/' + escapeAttribute(sesion.id) + '/arqueo">' +
                        (accion.icono || '<i class="fa-solid fa-lock"></i>') +
                        '</button>';
                }
            });
        }

        html += '</div>';

        return html;
    }

    function crearFilaSesion(sesion) {
        const fila = document.createElement('tr');

        const usuario = sesion.usuario_apertura_nombre
            ? escapeHtml(sesion.usuario_apertura_nombre)
            : '<span class="text-secondary">—</span>';

        const cierre = sesion.fecha_cierre_formateada
            ? '<span class="text-secondary">' +
              escapeHtml(sesion.fecha_cierre_formateada) +
              '</span>'
            : '<span class="text-secondary">—</span>';

        fila.innerHTML = `
            <td>
                <div class="fw-semibold">
                    ${escapeHtml(sesion.caja_nombre)}
                </div>
            </td>

            <td>
                ${usuario}
            </td>

            <td data-order="${escapeAttribute(sesion.fecha_apertura_orden)}">
                <span class="text-secondary">
                    ${escapeHtml(sesion.fecha_apertura_formateada)}
                </span>
            </td>

            <td>
                ${cierre}
            </td>

            <td>
                ${escapeHtml(formatoMonto(sesion.fondo_inicial_mostrado))}
            </td>

            <td>
                ${badgeEstado(sesion.estado)}
            </td>

            <td class="text-end px-4">
                ${crearAccionesSesion(sesion)}
            </td>
        `;

        return fila;
    }

    if (document.querySelector('#tablaSesiones')) {
        tablaSesiones = new DataTable('#tablaSesiones', {
            autoWidth: false,

            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron sesiones',
                emptyTable: 'No hay sesiones registradas',

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
                [2, 'desc']
            ],

            columnDefs: [
                {
                    targets: 2,
                    type: 'string'
                },
                {
                    orderable: false,
                    searchable: false,
                    targets: 6,
                    width: '12%'
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

    function ajustarFila(fila) {
        if (!fila) {
            return;
        }

        const celdas = fila.children;

        if (celdas.length < 7) {
            return;
        }

        celdas[0].style.width = '16%';
        celdas[1].style.width = '18%';
        celdas[2].style.width = '15%';
        celdas[3].style.width = '15%';
        celdas[4].style.width = '14%';
        celdas[5].style.width = '10%';
        celdas[6].style.width = '12%';

        celdas[6].classList.add('text-end', 'px-4');
    }

    function ajustarTodasLasFilas() {
        if (!tablaSesiones) {
            return;
        }

        tablaSesiones.rows().every(function () {
            ajustarFila(this.node());
        });
    }

    function agregarSesionATabla(sesion) {
        if (!tablaSesiones || !sesion) {
            return;
        }

        if (
            window.cajaFiltroId &&
            String(window.cajaFiltroId) !== String(sesion.caja_id)
        ) {
            return;
        }

        tablaSesiones.row.add(crearFilaSesion(sesion)).draw(false);

        ajustarTodasLasFilas();
        tablaSesiones.columns.adjust();
    }

    function actualizarSesionEnTabla(sesion) {
        if (!tablaSesiones || !sesion) {
            return;
        }

        let filaEncontrada = null;

        tablaSesiones.rows().every(function () {
            const fila = this.node();

            if (!fila || filaEncontrada) {
                return;
            }

            const boton = fila.querySelector(
                '.sesion-action-btn[data-id="' + sesion.id + '"]'
            );

            if (boton) {
                filaEncontrada = this;
            }
        });

        if (!filaEncontrada) {
            return;
        }
        filaEncontrada.remove();

        tablaSesiones.row.add(crearFilaSesion(sesion)).draw(false);

        ajustarTodasLasFilas();
        tablaSesiones.columns.adjust();
    }

    if (formAbrirSesion) {
        formAbrirSesion.addEventListener('submit', function (event) {
            event.preventDefault();

            const selectCaja = document.getElementById('abrir_caja_id');
            const inputFondo = document.getElementById('abrir_fondo_inicial');

            if (!selectCaja.value) {
                window.showToast('error', 'Debes seleccionar una caja.');

                selectCaja.focus();

                return;
            }

            if (inputFondo.value === '' || Number(inputFondo.value) < 0) {
                window.showToast(
                    'error',
                    'El fondo inicial es obligatorio y no puede ser negativo.'
                );

                inputFondo.focus();

                return;
            }

            const botonSubmit = formAbrirSesion.querySelector(
                'button[type="submit"]'
            );

            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;

            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Guardando...';

            peticion(
                formAbrirSesion.action,
                'POST',
                new FormData(formAbrirSesion)
            )
                .then(function (data) {
                    if (modalAbrirSesion) {
                        modalAbrirSesion.hide();
                    }

                    window.showToast(
                        'success',
                        data.mensaje || 'Caja abierta correctamente.'
                    );

                    agregarSesionATabla(data.sesion);

                    formAbrirSesion.reset();
                })
                .catch(mostrarError)
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });
    }

    function texto(id, valor) {
        const elemento = document.getElementById(id);

        if (elemento) {
            elemento.textContent =
                valor === null || valor === undefined || valor === ''
                    ? '—'
                    : valor;
        }
    }

    function llenarDetalle(sesion) {
        texto('det_caja', sesion.caja_nombre);
        texto('det_fondo', formatoMonto(sesion.fondo_inicial_mostrado));
        texto('det_usuario_apertura', sesion.usuario_apertura_nombre);
        texto('det_apertura', sesion.fecha_apertura_formateada);
        texto('det_obs_apertura', sesion.observaciones_apertura);
        texto('det_usuario_cierre', sesion.usuario_cierre_nombre);
        texto('det_cierre', sesion.fecha_cierre_formateada);
        texto('det_autorizo', sesion.usuario_autorizacion_nombre);

        document.getElementById('det_estado').innerHTML =
            badgeEstado(sesion.estado);

        texto('det_entradas', formatoMonto(sesion.entradas_mostradas));
        texto('det_salidas', formatoMonto(sesion.salidas_mostradas));

        const cerrada = sesion.estado !== 'abierta';

        texto(
            'det_esperado_etiqueta',
            cerrada ? 'Efectivo esperado' : 'Efectivo esperado (actual)'
        );

        texto(
            'det_esperado',
            formatoMonto(
                cerrada && sesion.efectivo_esperado_mostrado !== null
                    ? sesion.efectivo_esperado_mostrado
                    : sesion.esperado_actual_mostrado
            )
        );

        if (cerrada && sesion.efectivo_contado_mostrado !== null) {
            texto(
                'det_contado',
                formatoMonto(sesion.efectivo_contado_mostrado) +
                ' / ' +
                formatoMonto(sesion.diferencia_mostrada)
            );
        } else {
            texto('det_contado', '—');
        }

        const cuerpo = document.getElementById('det_movimientos');

        const movimientos = Array.isArray(sesion.movimientos)
            ? sesion.movimientos
            : [];

        if (movimientos.length === 0) {
            cuerpo.innerHTML =
                '<tr><td colspan="5" class="text-center text-secondary py-3">' +
                'Esta sesión no tiene movimientos.' +
                '</td></tr>';

            return;
        }

        cuerpo.innerHTML = movimientos.map(function (movimiento) {
            const esEntrada = movimiento.tipo === 'entrada';

            return (
                '<tr>' +
                '<td class="text-secondary">' +
                escapeHtml(movimiento.fecha_formateada) +
                '</td>' +
                '<td>' + escapeHtml(movimiento.concepto) + '</td>' +
                '<td>' +
                (
                    esEntrada
                        ? '<span class="badge text-bg-success">Entrada</span>'
                        : '<span class="badge text-bg-danger">Salida</span>'
                ) +
                '</td>' +
                '<td>' + escapeHtml(movimiento.usuario || '—') + '</td>' +
                '<td class="text-end">' +
                (esEntrada ? '+' : '−') +
                escapeHtml(formatoMonto(movimiento.monto_mostrado)) +
                '</td>' +
                '</tr>'
            );
        }).join('');
    }

    if (modalDetalleSesionEl) {
        const cargando = document.getElementById('detalleSesionCargando');
        const contenido = document.getElementById('detalleSesionContenido');

        modalDetalleSesionEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;
            const url = button.dataset.url || `/sesiones-caja/${id}`;

            cargando.classList.remove('d-none');
            contenido.classList.add('d-none');

            peticion(url, 'GET')
                .then(function (data) {
                    llenarDetalle(data.sesion);

                    cargando.classList.add('d-none');
                    contenido.classList.remove('d-none');
                })
                .catch(function (error) {
                    mostrarError(error);

                    bootstrap.Modal.getInstance(modalDetalleSesionEl)?.hide();
                });
        });
    }

    const modalRetiroSesionEl = document.getElementById('modalRetiroSesion');
    const formRetiroSesion = document.getElementById('formRetiroSesion');

    const modalRetiroSesion = modalRetiroSesionEl
        ? new bootstrap.Modal(modalRetiroSesionEl)
        : null;

    if (modalRetiroSesionEl && formRetiroSesion) {
        const inputMontoRetiro = document.getElementById('retiro_monto');
        const textoDisponible = document.getElementById('retiro_disponible');

        let retiroMax = null;
        let botonRetiroActual = null;

        function limitarMonto() {
            if (inputMontoRetiro.value === '') {
                return;
            }

            const valor = parseFloat(inputMontoRetiro.value);

            if (isNaN(valor)) {
                return;
            }

            if (valor < 0) {
                inputMontoRetiro.value = '';

                return;
            }

            if (retiroMax !== null && valor > retiroMax) {
                inputMontoRetiro.value = retiroMax.toFixed(2);
            }
        }

        inputMontoRetiro.addEventListener('input', limitarMonto);
        inputMontoRetiro.addEventListener('blur', limitarMonto);

        modalRetiroSesionEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;

            formRetiroSesion.reset();
            formRetiroSesion.action = button.dataset.url || `/sesiones-caja/${id}/retiros`;

            texto('retiro_caja_nombre', button.dataset.caja);

            botonRetiroActual = button;

            const disponible = parseFloat(button.dataset.disponible);

            retiroMax = isNaN(disponible) ? null : Math.max(disponible, 0);

            if (retiroMax === null) {
                inputMontoRetiro.removeAttribute('max');
                textoDisponible.textContent = '';
            } else {
                inputMontoRetiro.max = retiroMax.toFixed(2);

                textoDisponible.textContent = retiroMax > 0
                    ? 'Disponible en caja: ' + formatoMonto(retiroMax)
                    : 'No hay efectivo disponible en esta caja.';
            }
        });

        modalRetiroSesionEl.addEventListener('hidden.bs.modal', function () {
            formRetiroSesion.reset();
            formRetiroSesion.action = '';
        });

        formRetiroSesion.addEventListener('submit', function (event) {
            event.preventDefault();

            const inputMonto = document.getElementById('retiro_monto');
            const selectConcepto = document.getElementById('retiro_concepto');

            if (inputMonto.value === '' || Number(inputMonto.value) <= 0) {
                window.showToast('error', 'El monto debe ser mayor que cero.');

                inputMonto.focus();

                return;
            }

            if (!selectConcepto.value) {
                window.showToast('error', 'Debes seleccionar el concepto del movimiento.');

                selectConcepto.focus();

                return;
            }

            if (retiroMax !== null && Number(inputMonto.value) > retiroMax) {
                window.showToast(
                    'error',
                    'El monto supera el efectivo disponible en caja (' +
                    formatoMonto(retiroMax) + ').'
                );

                inputMonto.value = retiroMax.toFixed(2);

                return;
            }

            const botonSubmit = document.getElementById('btnRetiroSesion');
            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;

            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Guardando...';

            peticion(
                formRetiroSesion.action,
                'POST',
                new FormData(formRetiroSesion)
            )
                .then(function (data) {
                    if (modalRetiroSesion) {
                        modalRetiroSesion.hide();
                    }

                    window.showToast(
                        'success',
                        data.mensaje || 'Movimiento registrado correctamente.'
                    );

                    if (
                        botonRetiroActual &&
                        data.efectivo_disponible_mostrado !== undefined
                    ) {
                        botonRetiroActual.dataset.disponible =
                            data.efectivo_disponible_mostrado;
                    }
                })
                .catch(mostrarError)
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });
    }

    const modalCerrarSesionEl = document.getElementById('modalCerrarSesion');
    const formCerrarSesion = document.getElementById('formCerrarSesion');

    const modalCerrarSesion = modalCerrarSesionEl
        ? new bootstrap.Modal(modalCerrarSesionEl)
        : null;

    if (modalCerrarSesionEl && formCerrarSesion) {
        const arqueoCargando = document.getElementById('cerrarArqueoCargando');
        const arqueoContenido = document.getElementById('cerrarArqueoContenido');
        const inputContado = document.getElementById('cerrar_efectivo_contado');
        const textoDiferencia = document.getElementById('cerrar_diferencia');

        function actualizarDiferencia() {
            textoDiferencia.textContent = '';
            textoDiferencia.className = 'small mt-1';

            const esperado = parseFloat(formCerrarSesion.dataset.esperado);

            if (inputContado.value === '' || isNaN(esperado)) {
                return;
            }

            const diferencia =
                Math.round((parseFloat(inputContado.value) - esperado) * 100) / 100;

            if (diferencia === 0) {
                textoDiferencia.textContent = 'Sin diferencia.';
                textoDiferencia.classList.add('text-success');
            } else if (diferencia > 0) {
                textoDiferencia.textContent =
                    'Sobrante: ' + formatoMonto(diferencia);
                textoDiferencia.classList.add('text-warning');
            } else {
                textoDiferencia.textContent =
                    'Faltante: ' + formatoMonto(Math.abs(diferencia));
                textoDiferencia.classList.add('text-danger');
            }
        }

        inputContado.addEventListener('input', actualizarDiferencia);

        modalCerrarSesionEl.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            if (!button) {
                return;
            }

            const id = button.dataset.id;

            formCerrarSesion.reset();
            formCerrarSesion.action = button.dataset.url || `/sesiones-caja/${id}/cerrar`;
            formCerrarSesion.dataset.esperado = '';

            texto('cerrar_caja_nombre', button.dataset.caja);

            actualizarDiferencia();

            arqueoCargando.classList.remove('d-none');
            arqueoContenido.classList.add('d-none');

            peticion(
                button.dataset.arqueoUrl || `/sesiones-caja/${id}/arqueo`,
                'GET'
            )
                .then(function (data) {
                    const arqueo = data.arqueo;

                    texto('cerrar_fondo', formatoMonto(arqueo.fondo_inicial_mostrado));
                    texto('cerrar_entradas', formatoMonto(arqueo.entradas_mostradas));
                    texto('cerrar_salidas', formatoMonto(arqueo.salidas_mostradas));
                    texto('cerrar_esperado', formatoMonto(arqueo.efectivo_esperado_mostrado));

                    formCerrarSesion.dataset.esperado =
                        arqueo.efectivo_esperado_mostrado;

                    arqueoCargando.classList.add('d-none');
                    arqueoContenido.classList.remove('d-none');

                    actualizarDiferencia();
                })
                .catch(function (error) {
                    mostrarError(error);

                    bootstrap.Modal.getInstance(modalCerrarSesionEl)?.hide();
                });
        });

        modalCerrarSesionEl.addEventListener('hidden.bs.modal', function () {
            formCerrarSesion.reset();
            formCerrarSesion.action = '';
            formCerrarSesion.dataset.esperado = '';
        });

        formCerrarSesion.addEventListener('submit', function (event) {
            event.preventDefault();

            const selectAutorizador = document.getElementById('cerrar_autorizador');
            const inputPassword = document.getElementById('cerrar_password');

            if (inputContado.value === '' || Number(inputContado.value) < 0) {
                window.showToast(
                    'error',
                    'Debes ingresar el efectivo contado (no puede ser negativo).'
                );

                inputContado.focus();

                return;
            }

            if (!selectAutorizador.value) {
                window.showToast(
                    'error',
                    'Debes seleccionar al administrador que autoriza el cierre.'
                );

                selectAutorizador.focus();

                return;
            }

            if (!inputPassword.value) {
                window.showToast(
                    'error',
                    'La contraseña del administrador es obligatoria.'
                );

                inputPassword.focus();

                return;
            }

            const botonSubmit = document.getElementById('btnCerrarSesion');
            const textoOriginal = botonSubmit.innerHTML;

            botonSubmit.disabled = true;

            botonSubmit.innerHTML =
                '<span class="spinner-border spinner-border-sm me-2"></span>' +
                'Guardando...';

            peticion(
                formCerrarSesion.action,
                'POST',
                new FormData(formCerrarSesion)
            )
                .then(function (data) {
                    if (modalCerrarSesion) {
                        modalCerrarSesion.hide();
                    }

                    window.showToast(
                        'success',
                        data.mensaje || 'Caja cerrada correctamente.'
                    );

                    actualizarSesionEnTabla(data.sesion);
                })
                .catch(function (error) {
                    inputPassword.value = '';

                    mostrarError(error);
                })
                .finally(function () {
                    botonSubmit.disabled = false;
                    botonSubmit.innerHTML = textoOriginal;
                });
        });
    }

    ajustarTodasLasFilas();

    if (tablaSesiones) {
        tablaSesiones.columns.adjust();
    }
});
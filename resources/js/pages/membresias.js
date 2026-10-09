function avisarTicket(tipo, mensaje) {
    if (window.showToast) {
        window.showToast(tipo, mensaje);
    }
}

function escaparHtmlTicket(texto) {
    const div = document.createElement('div');
    div.textContent = texto || '';

    return div.innerHTML;
}

function obtenerHtmlTicket(url) {
    return fetch(url, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error(
                    response.status === 403
                        ? 'No tienes permiso para ver el ticket.'
                        : 'No se pudo cargar el ticket.'
                );
            }

            return response.text();
        })
        .then(function (html) {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const tarjeta = doc.querySelector('.ticket-card');

            if (!tarjeta) {
                throw new Error('No se encontró el contenido del ticket.');
            }

            return tarjeta.outerHTML;
        });
}

function imprimirTicket(urlImprimir) {
    fetch(urlImprimir, {
        credentials: 'same-origin',
        headers: {
            'Accept': 'text/html',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(function (response) {
            if (!response.ok) {
                throw new Error(
                    response.status === 403
                        ? 'No tienes permiso para imprimir el ticket.'
                        : 'No se pudo cargar el ticket para imprimir.'
                );
            }

            return response.text();
        })
        .then(function (html) {
            document.querySelectorAll('iframe.ticket-print-frame').forEach(function (frame) {
                frame.remove();
            });

            const iframe = document.createElement('iframe');
            iframe.className = 'ticket-print-frame';
            iframe.setAttribute('aria-hidden', 'true');
            iframe.style.cssText =
                'position:fixed;left:-10000px;top:0;width:900px;height:1200px;border:0;';
            document.body.appendChild(iframe);

            const ventana = iframe.contentWindow;
            const imprimirNativo = ventana.print.bind(ventana);

            const base = new URL(urlImprimir, window.location.href).href;
            const preparar =
                '<base href="' + base + '">' +
                '<script>window.print = function () {};<\/script>';

            const contenido = /<head[^>]*>/i.test(html)
                ? html.replace(/<head[^>]*>/i, function (etiqueta) {
                    return etiqueta + preparar;
                })
                : preparar + html;

            const doc = ventana.document;
            doc.open();
            doc.write(contenido);
            doc.close();

            const recursos = Array.from(
                doc.querySelectorAll('link[rel="stylesheet"], img')
            ).map(function (elemento) {
                if (
                    (elemento.tagName === 'IMG' && elemento.complete) ||
                    (elemento.tagName === 'LINK' && elemento.sheet)
                ) {
                    return Promise.resolve();
                }

                return new Promise(function (resolver) {
                    elemento.addEventListener('load', resolver);
                    elemento.addEventListener('error', resolver);
                });
            });

            return Promise.race([
                Promise.all(recursos),
                new Promise(function (resolver) {
                    setTimeout(resolver, 1500);
                })
            ]).then(function () {
                setTimeout(function () {
                    ventana.onafterprint = function () {
                        iframe.remove();
                    };

                    ventana.focus();
                    imprimirNativo();
                }, 150);
            });
        })
        .catch(function (error) {
            avisarTicket('error', error.message);
        });
}

function inicializarModalTicket() {
    const modalTicket = document.getElementById('modalVerTicket');
    const contenido = document.getElementById('ticketContenido');
    const btnImprimir = document.getElementById('btnImprimirTicket');

    if (!modalTicket || !contenido || !btnImprimir) {
        return;
    }

    let urlActual = '';

    function abrirTicket(url) {
        urlActual = url;
        btnImprimir.disabled = true;

        contenido.innerHTML =
            '<div class="text-center py-5">' +
            '<div class="spinner-border" role="status"></div>' +
            '</div>';

        bootstrap.Modal.getOrCreateInstance(modalTicket).show();

        obtenerHtmlTicket(url)
            .then(function (tarjetaHtml) {
                contenido.innerHTML = tarjetaHtml;
                btnImprimir.disabled = false;
            })
            .catch(function (error) {
                contenido.innerHTML =
                    '<div class="alert alert-danger mb-0">' +
                    escaparHtmlTicket(error.message) +
                    '</div>';
            });
    }

    document.addEventListener('click', function (evento) {
        const boton = evento.target.closest('.btn-ver-ticket');

        if (boton) {
            abrirTicket(boton.dataset.url || '');
        }
    });

    btnImprimir.addEventListener('click', function () {
        if (urlActual) {
            imprimirTicket(urlActual.replace(/\/+$/, '') + '/imprimir');
        }
    });

    modalTicket.addEventListener('hidden.bs.modal', function () {
        urlActual = '';
        contenido.innerHTML = '';
    });
}

document.addEventListener('DOMContentLoaded', function () {
    inicializarModalTicket();

    const tabla = document.getElementById('tablaMembresias');

    if (!tabla) {
        return;
    }

    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';

    let dataTable = null;
    let observacionesOriginales = null;

    const modalNuevo = document.getElementById('modalNuevaMembresia');
    const modalEditar = document.getElementById('modalEditarMembresia');
    const modalRenovar = document.getElementById('modalRenovarMembresia');
    const modalCancelar = document.getElementById('modalCancelarMembresia');

    const formNuevo = document.getElementById('formNuevaMembresia');
    const formEditar = document.getElementById('formEditarMembresia');
    const formRenovar = document.getElementById('formRenovarMembresia');
    const formCancelar = document.getElementById('formCancelarMembresia');

    const acciones = Array.isArray(window.accionesMembresias)
        ? window.accionesMembresias
        : [];

    const moneda = window.monedaMembresias || {
        codigo: 'MXN',
        simbolo: '$',
        tasa: 1
    };

    function tieneAccion(slug) {
        return acciones.some(function (accion) {
            return accion.slug === slug;
        });
    }

    function temaSwal() {
        const cuerpo = getComputedStyle(document.body);
        const referencia = document.querySelector('.modal-content');

        let fondo = referencia
            ? getComputedStyle(referencia).backgroundColor
            : cuerpo.backgroundColor;

        if (!fondo || fondo === 'transparent' || fondo === 'rgba(0, 0, 0, 0)') {
            fondo = cuerpo.backgroundColor;
        }

        return { background: fondo, color: cuerpo.color };
    }

    function confirmarAccion(opciones) {
        const titulo = opciones.titulo;
        const texto = opciones.texto;
        const textoConfirmar = opciones.textoConfirmar;
        const claseConfirmar = opciones.claseConfirmar || 'btn btn-primary';

        if (!window.Swal) {
            return Promise.resolve(window.confirm(titulo));
        }

        return Swal.fire({
            ...temaSwal(),
            icon: 'warning',
            title: titulo,
            text: texto,
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: textoConfirmar,
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            heightAuto: false,
            customClass: {
                confirmButton: claseConfirmar + ' mx-1',
                cancelButton: 'btn btn-secondary mx-1'
            }
        }).then(function (resultado) {
            return resultado.isConfirmed;
        });
    }

    function mostrarTicketGenerado(data, mensaje) {
        const urls = (data && data.urls) || {};

        if (!window.Swal || !urls.ticket_imprimir) {
            mostrarExito(mensaje);
            return;
        }

        const folio = data.ticket && data.ticket.folio
            ? '<div class="mt-2">Folio: <strong>' +
            escapeHtml(data.ticket.folio) +
            '</strong></div>'
            : '';

        Swal.fire({
            ...temaSwal(),
            icon: 'question',
            title: mensaje,
            html: '¿Deseas imprimir el ticket?' + folio,
            showCancelButton: true,
            reverseButtons: true,
            confirmButtonText: 'Imprimir',
            cancelButtonText: 'Cancelar',
            buttonsStyling: false,
            heightAuto: false,
            customClass: {
                confirmButton: 'btn btn-primary mx-1',
                cancelButton: 'btn btn-secondary mx-1'
            }
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                imprimirTicket(urls.ticket_imprimir);
            }
        });
    }

    function urlsPorDefecto(id) {
        return {
            show: `/membresias/${id}`,
            update: `/membresias/${id}`,
            renovar: `/membresias/${id}/renovar`,
            cancelar: `/membresias/${id}/cancelar`
        };
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

    function formatearPrecio(precio) {
        const numero =
            Number.parseFloat(precio);

        if (!Number.isFinite(numero)) {
            return '—';
        }

        return moneda.simbolo +
            numero.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
    }

    function formatearPrecioTabla(precio) {
        const texto = formatearPrecio(precio);

        return texto === '—'
            ? texto
            : texto + ' ' + (moneda.codigo || '');
    }

    function formatearDuracion(dias) {
        const numero =
            Number(dias);

        if (!Number.isFinite(numero)) {
            return '—';
        }

        return numero +
            ' ' +
            (numero === 1
                ? 'día'
                : 'días');
    }

    function crearEstado(membresia) {
        let clase = 'text-bg-secondary';
        let texto = 'Desconocida';

        switch (membresia.estado) {
            case 'activa':
                clase = 'text-bg-success';
                texto = 'Activa';
                break;

            case 'vencida':
                clase = 'text-bg-secondary';
                texto = 'Vencida';
                break;

            case 'cancelada':
                clase = 'text-bg-danger';
                texto = 'Cancelada';
                break;

            default:
                texto =
                    membresia.estado
                        ? String(membresia.estado)
                            .charAt(0)
                            .toUpperCase() +
                        String(membresia.estado).slice(1)
                        : 'Desconocida';
                break;
        }

        return `
            <span class="badge rounded-pill ${clase}">
                ${escapeHtml(texto)}
            </span>
        `;
    }

    function crearBotonesAcciones(membresia) {
        let html =
            '<div class="membresia-actions">';

        acciones.forEach(function (accion) {

            if (accion.slug === 'membresias.ver') {

                if (!membresia.urls || !membresia.urls.show) {
                    return;
                }

                html +=
                    '<a href="' +
                    escapeAttribute(
                        membresia.urls.show
                    ) +
                    '" ' +
                    'class="btn btn-sm btn-outline-primary membresia-action-btn" ' +
                    'title="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '" ' +
                    'data-tooltip="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '">' +
                    (
                        accion.icono ||
                        '<i class="fa-solid fa-eye"></i>'
                    ) +
                    '</a>';
            }

            else if (
                accion.slug === 'membresias.editar'
            ) {

                if (
                    !membresia.urls ||
                    !membresia.urls.update
                ) {
                    return;
                }

                const nombre =
                    membresia.nombre_miembro ||
                    '';

                const plan =
                    membresia.plan_nombre ||
                    '';

                const observaciones =
                    membresia.observaciones ||
                    '';

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-secondary membresia-action-btn btn-editar-membresia" ' +
                    'data-id="' +
                    escapeAttribute(
                        membresia.id
                    ) +
                    '" ' +
                    'data-name="' +
                    escapeAttribute(
                        nombre
                    ) +
                    '" ' +
                    'data-plan="' +
                    escapeAttribute(
                        plan
                    ) +
                    '" ' +
                    'data-estado="' +
                    escapeAttribute(
                        membresia.estado
                    ) +
                    '" ' +
                    'data-observaciones="' +
                    escapeAttribute(
                        observaciones
                    ) +
                    '" ' +
                    'data-fecha-inicio="' +
                    escapeAttribute(
                        membresia.fecha_inicio_mostrada
                    ) +
                    '" ' +
                    'data-fecha-fin="' +
                    escapeAttribute(
                        membresia.fecha_fin_mostrada
                    ) +
                    '" ' +
                    'data-url="' +
                    escapeAttribute(
                        membresia.urls.update
                    ) +
                    '" ' +
                    'title="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '" ' +
                    'data-tooltip="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '">' +
                    (
                        accion.icono ||
                        '<i class="fa-solid fa-pen"></i>'
                    ) +
                    '</button>';
            }

            else if (
                accion.slug === 'membresias.renovar' &&
                membresia.estado !== 'cancelada'
            ) {

                if (
                    !membresia.urls ||
                    !membresia.urls.renovar
                ) {
                    return;
                }

                const nombre =
                    membresia.nombre_miembro ||
                    '';

                const plan =
                    membresia.plan_nombre ||
                    '';

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-success membresia-action-btn btn-renovar-membresia" ' +
                    'data-id="' +
                    escapeAttribute(
                        membresia.id
                    ) +
                    '" ' +
                    'data-name="' +
                    escapeAttribute(
                        nombre
                    ) +
                    '" ' +
                    'data-plan="' +
                    escapeAttribute(
                        plan
                    ) +
                    '" ' +
                    'data-estado="' +
                    escapeAttribute(
                        membresia.estado
                    ) +
                    '" ' +
                    'data-fecha-fin="' +
                    escapeAttribute(
                        membresia.fecha_fin_mostrada
                    ) +
                    '" ' +
                    'data-url="' +
                    escapeAttribute(
                        membresia.urls.renovar
                    ) +
                    '" ' +
                    'title="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '" ' +
                    'data-tooltip="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '">' +
                    (
                        accion.icono ||
                        '<i class="fa-solid fa-arrows-rotate"></i>'
                    ) +
                    '</button>';
            }

            else if (
                accion.slug === 'membresias.cancelar' &&
                membresia.estado === 'activa'
            ) {

                if (
                    !membresia.urls ||
                    !membresia.urls.cancelar
                ) {
                    return;
                }

                const nombre =
                    membresia.nombre_miembro ||
                    '';

                const plan =
                    membresia.plan_nombre ||
                    '';

                html +=
                    '<button type="button" ' +
                    'class="btn btn-sm btn-outline-danger membresia-action-btn btn-cancelar-membresia" ' +
                    'data-id="' +
                    escapeAttribute(
                        membresia.id
                    ) +
                    '" ' +
                    'data-name="' +
                    escapeAttribute(
                        nombre
                    ) +
                    '" ' +
                    'data-plan="' +
                    escapeAttribute(
                        plan
                    ) +
                    '" ' +
                    'data-url="' +
                    escapeAttribute(
                        membresia.urls.cancelar
                    ) +
                    '" ' +
                    'title="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '" ' +
                    'data-tooltip="' +
                    escapeAttribute(
                        accion.nombre
                    ) +
                    '">' +
                    (
                        accion.icono ||
                        '<i class="fa-solid fa-ban"></i>'
                    ) +
                    '</button>';
            }
        });

        html +=
            '</div>';

        return html;
    }

    function obtenerNombreMiembro(membresia) {
        if (membresia.nombre_miembro) {
            return membresia.nombre_miembro;
        }

        if (!membresia.persona) {
            return '';
        }

        return [
            membresia.persona.nombre,
            membresia.persona.apellido_paterno,
            membresia.persona.apellido_materno
        ]
            .filter(function (valor) {
                return valor;
            })
            .join(' ')
            .trim();
    }

    function obtenerNombrePlan(membresia) {
        if (membresia.plan_nombre) {
            return membresia.plan_nombre;
        }

        if (
            membresia.plan &&
            membresia.plan.nombre
        ) {
            return membresia.plan.nombre;
        }

        return '';
    }

    function obtenerPrecioMostrado(membresia) {
        if (
            membresia.precio_mostrado !== undefined &&
            membresia.precio_mostrado !== null
        ) {
            return Number.parseFloat(
                membresia.precio_mostrado
            );
        }

        if (
            membresia.precio !== undefined &&
            membresia.precio !== null
        ) {
            return Number.parseFloat(
                membresia.precio
            );
        }

        return NaN;
    }

    function obtenerFechaInicio(membresia) {
        return membresia.fecha_inicio_mostrada ||
            membresia.fecha_inicio ||
            '—';
    }

    function obtenerFechaFin(membresia) {
        return membresia.fecha_fin_mostrada ||
            membresia.fecha_fin ||
            '—';
    }

    function obtenerDuracion(membresia) {
        if (
            membresia.duracion_dias !== undefined &&
            membresia.duracion_dias !== null
        ) {
            return membresia.duracion_dias;
        }

        if (
            membresia.fecha_inicio &&
            membresia.fecha_fin
        ) {
            const inicio =
                new Date(
                    membresia.fecha_inicio +
                    'T00:00:00'
                );

            const fin =
                new Date(
                    membresia.fecha_fin +
                    'T00:00:00'
                );

            if (
                !Number.isNaN(inicio.getTime()) &&
                !Number.isNaN(fin.getTime())
            ) {
                const diferencia =
                    Math.round(
                        (
                            fin.getTime() -
                            inicio.getTime()
                        ) /
                        86400000
                    ) + 1;

                return diferencia;
            }
        }

        return null;
    }

    function normalizarMembresia(membresia, urls = null) {
        if (!membresia) {
            return null;
        }

        const nombreMiembro =
            obtenerNombreMiembro(
                membresia
            );

        const planNombre =
            obtenerNombrePlan(
                membresia
            );

        const membresiaNormalizada = {
            ...membresia,

            nombre_miembro:
                nombreMiembro,

            plan_nombre:
                planNombre,

            precio_mostrado:
                obtenerPrecioMostrado(
                    membresia
                ),

            fecha_inicio_mostrada:
                obtenerFechaInicio(
                    membresia
                ),

            fecha_fin_mostrada:
                obtenerFechaFin(
                    membresia
                ),

            duracion_dias:
                obtenerDuracion(
                    membresia
                ),

            observaciones:
                membresia.observaciones || '',

            moneda:
                membresia.moneda ||
                moneda
        };

        const urlsBase = urlsPorDefecto(membresia.id);
        const urlsOrigen = urls || membresia.urls || {};

        membresiaNormalizada.urls = {
            show: urlsOrigen.show || urlsBase.show,
            update: urlsOrigen.update || urlsBase.update,
            renovar: urlsOrigen.renovar || urlsBase.renovar,
            cancelar: urlsOrigen.cancelar || urlsBase.cancelar,
            ticket: urlsOrigen.ticket || membresia.ticket_url || '',
            ticket_imprimir: urlsOrigen.ticket_imprimir || ''
        };

        return membresiaNormalizada;
    }

    function crearFilaMembresia(
        membresia,
        urls = null
    ) {
        const datos =
            normalizarMembresia(
                membresia,
                urls
            );

        if (!datos) {
            return null;
        }

        const fila =
            document.createElement('tr');

        fila.dataset.id =
            datos.id;

        fila.dataset.nombre =
            datos.nombre_miembro || '';

        fila.dataset.plan =
            datos.plan_nombre || '';

        fila.dataset.estado =
            datos.estado || '';

        fila.dataset.precio =
            datos.precio_mostrado ?? '';

        fila.dataset.observaciones =
            datos.observaciones || '';

        fila.dataset.fechaInicio =
            datos.fecha_inicio_mostrada || '';

        fila.dataset.fechaFin =
            datos.fecha_fin_mostrada || '';

        fila.dataset.urlShow =
            datos.urls?.show || '';

        fila.dataset.urlUpdate =
            datos.urls?.update || '';

        fila.dataset.urlRenovar =
            datos.urls?.renovar || '';

        fila.dataset.urlCancelar =
            datos.urls?.cancelar || '';

        fila.dataset.urlTicket =
            datos.urls?.ticket || '';

        fila.dataset.urlTicketImprimir =
            datos.urls?.ticket_imprimir || '';

        fila.innerHTML = `
            <td>
                <div class="fw-semibold">
                    ${escapeHtml(
            datos.nombre_miembro
        )}
                </div>

                ${datos.persona &&
                datos.persona.email
                ? `
                            <div class="small text-secondary">
                                ${escapeHtml(
                    datos.persona.email
                )}
                            </div>
                        `
                : ''
            }
            </td>

            <td>
                <span class="fw-medium">
                    ${escapeHtml(
                datos.plan_nombre
            )}
                </span>
            </td>

            <td>
                <div>
                    ${escapeHtml(
                datos.fecha_inicio_mostrada
            )}
                    —
                    ${escapeHtml(
                datos.fecha_fin_mostrada
            )}
                </div>

                ${datos.duracion_dias
                ? `
                            <div class="small text-secondary">
                                ${escapeHtml(
                    formatearDuracion(
                        datos.duracion_dias
                    )
                )}
                            </div>
                        `
                : ''
            }
            </td>

            <td>
                <span class="fw-semibold">
                    ${escapeHtml(
                formatearPrecioTabla(datos.precio_mostrado)
            )}
                </span>
            </td>

            <td>
                ${crearEstado(datos)}
            </td>

            <td class="text-end px-4">
                ${crearBotonesAcciones(datos)}
            </td>
        `;

        return fila;
    }

    function obtenerDatosFila(fila) {
        if (!fila) {
            return null;
        }

        return {
            id:
                fila.dataset.id || '',

            nombre_miembro:
                fila.dataset.nombre || '',

            plan_nombre:
                fila.dataset.plan || '',

            estado:
                fila.dataset.estado || '',

            precio_mostrado:
                fila.dataset.precio || '',

            observaciones:
                fila.dataset.observaciones || '',

            fecha_inicio_mostrada:
                fila.dataset.fechaInicio || '',

            fecha_fin_mostrada:
                fila.dataset.fechaFin || '',

            urls: {
                show:
                    fila.dataset.urlShow || '',

                update:
                    fila.dataset.urlUpdate || '',

                renovar:
                    fila.dataset.urlRenovar || '',

                cancelar:
                    fila.dataset.urlCancelar || '',

                ticket:
                    fila.dataset.urlTicket || '',

                ticket_imprimir:
                    fila.dataset.urlTicketImprimir || ''
            }
        };
    }

    function actualizarMembresiaEnTabla(
        membresia,
        urls = null
    ) {
        if (!dataTable || !membresia) {
            return;
        }

        const datos =
            normalizarMembresia(
                membresia,
                urls
            );

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
                    String(datos.id)
                ) {
                    return;
                }

                if (
                    !datos.urls.ticket &&
                    fila.dataset.urlTicket
                ) {
                    datos.urls.ticket =
                        fila.dataset.urlTicket;

                    datos.urls.ticket_imprimir =
                        fila.dataset.urlTicketImprimir || '';
                }

                const datosTabla =
                    this.data();

                datosTabla[0] = `
                    <div class="fw-semibold">
                        ${escapeHtml(
                    datos.nombre_miembro
                )}
                    </div>

                    ${datos.persona &&
                        datos.persona.email
                        ? `
                                <div class="small text-secondary">
                                    ${escapeHtml(
                            datos.persona.email
                        )}
                                </div>
                            `
                        : ''
                    }
                `;

                datosTabla[1] = `
                    <span class="fw-medium">
                        ${escapeHtml(
                    datos.plan_nombre
                )}
                    </span>
                `;

                datosTabla[2] = `
                    <div>
                        ${escapeHtml(
                    datos.fecha_inicio_mostrada
                )}
                        —
                        ${escapeHtml(
                    datos.fecha_fin_mostrada
                )}
                    </div>

                    ${datos.duracion_dias
                        ? `
                                <div class="small text-secondary">
                                    ${escapeHtml(
                            formatearDuracion(
                                datos.duracion_dias
                            )
                        )}
                                </div>
                            `
                        : ''
                    }
                `;

                datosTabla[3] = `
                    <span class="fw-semibold">
                        ${escapeHtml(
                    formatearPrecioTabla(datos.precio_mostrado)
                )}
                    </span>
                `;

                datosTabla[4] =
                    crearEstado(
                        datos
                    );

                datosTabla[5] =
                    crearBotonesAcciones(
                        datos
                    );

                this.data(
                    datosTabla
                );

                fila.dataset.id =
                    datos.id;

                fila.dataset.nombre =
                    datos.nombre_miembro || '';

                fila.dataset.plan =
                    datos.plan_nombre || '';

                fila.dataset.estado =
                    datos.estado || '';

                fila.dataset.precio =
                    datos.precio_mostrado ?? '';

                fila.dataset.observaciones =
                    datos.observaciones || '';

                fila.dataset.fechaInicio =
                    datos.fecha_inicio_mostrada || '';

                fila.dataset.fechaFin =
                    datos.fecha_fin_mostrada || '';

                fila.dataset.urlShow =
                    datos.urls?.show || '';

                fila.dataset.urlUpdate =
                    datos.urls?.update || '';

                fila.dataset.urlRenovar =
                    datos.urls?.renovar || '';

                fila.dataset.urlCancelar =
                    datos.urls?.cancelar || '';

                fila.dataset.urlTicket =
                    datos.urls?.ticket || '';

                fila.dataset.urlTicketImprimir =
                    datos.urls?.ticket_imprimir || '';
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
            tabla.querySelector(
                'tbody'
            );

        if (!tbody) {
            return;
        }

        tbody.innerHTML = `
            <tr class="empty-row">
                <td colspan="6" class="text-center py-5">
                    <div class="text-secondary">
                        <i class="fa-solid fa-id-card-clip fa-2x mb-3"></i>
                        <div class="fw-semibold">
                            No hay membresías registradas.
                        </div>
                    </div>
                </td>
            </tr>
        `;
    }

    function agregarMembresiaATabla(
        membresia,
        urls = null
    ) {
        if (!dataTable) {
            return;
        }

        quitarFilaVacia();

        const fila =
            crearFilaMembresia(
                membresia,
                urls
            );

        if (!fila) {
            return;
        }

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

    function peticion(
        url,
        method,
        body = null
    ) {
        const opciones = {
            method,
            headers: {
                'X-CSRF-TOKEN':
                    csrfToken,

                'Accept':
                    'application/json',

                'X-Requested-With':
                    'XMLHttpRequest',
            },
        };

        if (body instanceof FormData) {
            opciones.body =
                body;
        }

        else if (body) {
            opciones.headers[
                'Content-Type'
            ] =
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
                    .catch(
                        function () {
                            return {};
                        }
                    );

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
        if (!modal) {
            return;
        }

        const instancia =
            bootstrap.Modal.getInstance(
                modal
            );

        if (instancia) {
            instancia.hide();
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

    function obtenerPrecioPlanSeleccionado(selectId) {
        const select =
            document.getElementById(
                selectId
            );

        if (!select) {
            return NaN;
        }

        const opcion =
            select.options[
            select.selectedIndex
            ];

        if (
            !opcion ||
            !opcion.value
        ) {
            return NaN;
        }

        const precio =
            opcion.getAttribute(
                'data-precio'
            );

        const numero =
            Number.parseFloat(
                precio
            );

        return Number.isFinite(numero)
            ? numero
            : NaN;
    }

    function actualizarPrecioPlan() {
        const planSelect =
            document.getElementById(
                'plan_id'
            );

        const precioPlan =
            document.getElementById(
                'precioPlan'
            );

        if (
            !planSelect ||
            !precioPlan
        ) {
            return;
        }

        const precio =
            obtenerPrecioPlanSeleccionado(
                'plan_id'
            );

        if (!Number.isFinite(precio)) {
            precioPlan.textContent =
                '—';

            actualizarCambioNuevo();

            return;
        }

        precioPlan.textContent =
            formatearPrecio(
                precio
            );

        actualizarCambioNuevo();
    }

    function actualizarPrecioRenovacion() {
        const planSelect =
            document.getElementById(
                'renovar_plan_id'
            );

        const precioPlan =
            document.getElementById(
                'precioRenovacion'
            );

        if (
            !planSelect ||
            !precioPlan
        ) {
            return;
        }

        const precio =
            obtenerPrecioPlanSeleccionado(
                'renovar_plan_id'
            );

        if (!Number.isFinite(precio)) {
            precioPlan.textContent =
                '—';

            actualizarCambioRenovacion();

            return;
        }

        precioPlan.textContent =
            formatearPrecio(
                precio
            );

        actualizarCambioRenovacion();
    }

    function actualizarPeriodoRenovacion() {
        const planSelect =
            document.getElementById(
                'renovar_plan_id'
            );

        const periodoInfo =
            document.getElementById(
                'renovar_periodo_info'
            );

        if (
            !planSelect ||
            !periodoInfo
        ) {
            return;
        }

        const opcion =
            planSelect.options[
            planSelect.selectedIndex
            ];

        if (
            !opcion ||
            !opcion.value
        ) {
            periodoInfo.textContent =
                'Selecciona un plan para calcular el nuevo periodo de la membresía.';

            return;
        }

        const duracion =
            opcion.getAttribute(
                'data-duracion'
            );

        const numero =
            Number(duracion);

        if (
            !Number.isFinite(numero) ||
            numero <= 0
        ) {
            periodoInfo.textContent =
                'El plan seleccionado no tiene una duración válida.';

            return;
        }

        periodoInfo.textContent =
            'La nueva membresía tendrá una duración de ' +
            formatearDuracion(numero) +
            '.';
    }

    function actualizarMetodoPagoNuevo() {
        const metodoPago =
            document.getElementById(
                'metodo_pago'
            );

        const referenciaContainer =
            document.getElementById(
                'referenciaContainer'
            );

        const montoRecibidoContainer =
            document.getElementById(
                'montoRecibidoContainer'
            );

        const cambioContainer =
            document.getElementById(
                'cambioContainer'
            );

        const montoRecibido =
            document.getElementById(
                'monto_recibido'
            );

        if (
            !metodoPago ||
            !referenciaContainer ||
            !montoRecibidoContainer ||
            !cambioContainer
        ) {
            return;
        }

        const efectivo =
            metodoPago.value === 'efectivo';

        referenciaContainer.classList.toggle(
            'd-none',
            efectivo
        );

        montoRecibidoContainer.classList.toggle(
            'd-none',
            !efectivo
        );

        cambioContainer.classList.toggle(
            'd-none',
            !efectivo
        );

        if (!efectivo && montoRecibido) {
            montoRecibido.value =
                '';
        }

        if (efectivo) {
            actualizarCambioNuevo();
        }
    }

    function actualizarMetodoPagoRenovacion() {
        const metodoPago =
            document.getElementById(
                'renovar_metodo_pago'
            );

        const referenciaContainer =
            document.getElementById(
                'renovarReferenciaContainer'
            );

        const montoRecibidoContainer =
            document.getElementById(
                'renovarMontoRecibidoContainer'
            );

        const cambioContainer =
            document.getElementById(
                'renovarCambioContainer'
            );

        const montoRecibido =
            document.getElementById(
                'renovar_monto_recibido'
            );

        if (
            !metodoPago ||
            !referenciaContainer ||
            !montoRecibidoContainer ||
            !cambioContainer
        ) {
            return;
        }

        const efectivo =
            metodoPago.value === 'efectivo';

        referenciaContainer.classList.toggle(
            'd-none',
            efectivo
        );

        montoRecibidoContainer.classList.toggle(
            'd-none',
            !efectivo
        );

        cambioContainer.classList.toggle(
            'd-none',
            !efectivo
        );

        if (!efectivo && montoRecibido) {
            montoRecibido.value =
                '';
        }

        if (efectivo) {
            actualizarCambioRenovacion();
        }
    }

    function calcularCambio(
        precio,
        recibido
    ) {
        if (
            !Number.isFinite(precio) ||
            !Number.isFinite(recibido)
        ) {
            return null;
        }

        return Math.round(
            (
                recibido -
                precio
            ) *
            100
        ) / 100;
    }

    function actualizarCambioNuevo() {
        const cambio =
            document.getElementById(
                'cambio'
            );

        const montoRecibido =
            document.getElementById(
                'monto_recibido'
            );

        if (
            !cambio ||
            !montoRecibido
        ) {
            return;
        }

        const precio =
            obtenerPrecioPlanSeleccionado(
                'plan_id'
            );

        const recibido =
            Number.parseFloat(
                montoRecibido.value
            );

        const resultado =
            calcularCambio(
                precio,
                recibido
            );

        if (resultado === null) {
            cambio.textContent =
                '—';

            return;
        }

        if (resultado < 0) {
            cambio.textContent =
                'Monto insuficiente';

            return;
        }

        cambio.textContent =
            formatearPrecio(
                resultado
            );
    }

    function actualizarCambioRenovacion() {
        const cambio =
            document.getElementById(
                'renovarCambio'
            );

        const montoRecibido =
            document.getElementById(
                'renovar_monto_recibido'
            );

        if (
            !cambio ||
            !montoRecibido
        ) {
            return;
        }

        const precio =
            obtenerPrecioPlanSeleccionado(
                'renovar_plan_id'
            );

        const recibido =
            Number.parseFloat(
                montoRecibido.value
            );

        const resultado =
            calcularCambio(
                precio,
                recibido
            );

        if (resultado === null) {
            cambio.textContent =
                '—';

            return;
        }

        if (resultado < 0) {
            cambio.textContent =
                'Monto insuficiente';

            return;
        }

        cambio.textContent =
            formatearPrecio(
                resultado
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
                    '#tablaMembresias',
                    {
                        autoWidth: false,

                        language: {
                            search: 'Buscar:',
                            lengthMenu: 'Mostrar _MENU_ registros',
                            info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                            infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                            infoFiltered: '(filtrado de _MAX_ registros)',
                            zeroRecords: 'No se encontraron membresías',
                            emptyTable: 'No hay membresías registradas',

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
                $('#tablaMembresias').DataTable({
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
                        zeroRecords: 'No se encontraron membresías',
                        emptyTable: 'No hay membresías registradas',

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

    const planSelect =
        document.getElementById(
            'plan_id'
        );

    const renovarPlanSelect =
        document.getElementById(
            'renovar_plan_id'
        );

    const metodoPagoNuevo =
        document.getElementById(
            'metodo_pago'
        );

    const montoRecibidoNuevo =
        document.getElementById(
            'monto_recibido'
        );

    const metodoPagoRenovacion =
        document.getElementById(
            'renovar_metodo_pago'
        );

    const montoRecibidoRenovacion =
        document.getElementById(
            'renovar_monto_recibido'
        );

    planSelect?.addEventListener(
        'change',
        actualizarPrecioPlan
    );

    renovarPlanSelect?.addEventListener(
        'change',
        function () {
            actualizarPrecioRenovacion();
            actualizarPeriodoRenovacion();
        }
    );

    metodoPagoNuevo?.addEventListener(
        'change',
        actualizarMetodoPagoNuevo
    );

    montoRecibidoNuevo?.addEventListener(
        'input',
        actualizarCambioNuevo
    );

    metodoPagoRenovacion?.addEventListener(
        'change',
        actualizarMetodoPagoRenovacion
    );

    montoRecibidoRenovacion?.addEventListener(
        'input',
        actualizarCambioRenovacion
    );

    formNuevo?.addEventListener(
        'submit',
        async function (evento) {

            evento.preventDefault();

            if (
                metodoPagoNuevo &&
                metodoPagoNuevo.value === 'efectivo'
            ) {
                const precio =
                    obtenerPrecioPlanSeleccionado(
                        'plan_id'
                    );

                const recibido =
                    Number.parseFloat(
                        montoRecibidoNuevo?.value
                    );

                if (
                    !Number.isFinite(precio) ||
                    !Number.isFinite(recibido) ||
                    recibido < precio
                ) {
                    mostrarError(
                        new Error(
                            'El monto recibido debe ser igual o mayor al precio de la membresía.'
                        )
                    );

                    return;
                }
            }

            const confirmado =
                await confirmarAccion({
                    titulo: '¿Contratar membresía?',
                    texto: 'Se registrará el pago y se generará el ticket.',
                    textoConfirmar: 'Contratar'
                });

            if (!confirmado) {
                return;
            }

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

                    agregarMembresiaATabla(
                        data.membresia,
                        data.urls
                    );

                    resetFormulario(
                        formNuevo
                    );

                    actualizarPrecioPlan();
                    actualizarMetodoPagoNuevo();

                    mostrarTicketGenerado(
                        data,
                        data.mensaje ||
                        'Membresía contratada correctamente.'
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

    formEditar?.addEventListener(
        'submit',
        function (evento) {

            evento.preventDefault();

            const observacionesActuales =
                String(
                    document.getElementById('editar_observaciones')?.value || ''
                ).trim();

            if (observacionesActuales === (observacionesOriginales ?? '')) {
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

            const formData =
                new FormData(
                    formEditar
                );

            peticion(
                formEditar.action,
                'POST',
                formData
            )
                .then(function (data) {

                    if (data.membresia) {

                        actualizarMembresiaEnTabla(
                            data.membresia,
                            data.urls
                        );
                    }

                    cerrarModal(
                        modalEditar
                    );

                    mostrarExito(
                        data.mensaje ||
                        'Membresía actualizada correctamente.'
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

    formRenovar?.addEventListener(
        'submit',
        async function (evento) {

            evento.preventDefault();

            if (
                metodoPagoRenovacion &&
                metodoPagoRenovacion.value === 'efectivo'
            ) {
                const precio =
                    obtenerPrecioPlanSeleccionado(
                        'renovar_plan_id'
                    );

                const recibido =
                    Number.parseFloat(
                        montoRecibidoRenovacion?.value
                    );

                if (
                    !Number.isFinite(precio) ||
                    !Number.isFinite(recibido) ||
                    recibido < precio
                ) {
                    mostrarError(
                        new Error(
                            'El monto recibido debe ser igual o mayor al precio de la membresía.'
                        )
                    );

                    return;
                }
            }

            const confirmado =
                await confirmarAccion({
                    titulo: '¿Renovar membresía?',
                    texto: 'Se registrará el pago y se generará el ticket.',
                    textoConfirmar: 'Renovar',
                    claseConfirmar: 'btn btn-success'
                });

            if (!confirmado) {
                return;
            }

            const boton =
                formRenovar.querySelector(
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
                    formRenovar
                );

            peticion(
                formRenovar.action,
                'POST',
                formData
            )
                .then(function (data) {

                    if (data.membresia) {

                        actualizarMembresiaEnTabla(
                            data.membresia,
                            data.urls
                        );
                    }

                    cerrarModal(
                        modalRenovar
                    );

                    mostrarTicketGenerado(
                        data,
                        data.mensaje ||
                        'Membresía renovada correctamente.'
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

    formCancelar?.addEventListener(
        'submit',
        function (evento) {

            evento.preventDefault();

            const boton =
                formCancelar.querySelector(
                    'button[type="submit"]'
                );

            const textoOriginal =
                boton?.innerHTML;

            if (boton) {
                boton.disabled =
                    true;

                boton.innerHTML =
                    '<span class="spinner-border spinner-border-sm me-2"></span>' +
                    'Cancelando...';
            }

            const formData =
                new FormData(
                    formCancelar
                );

            peticion(
                formCancelar.action,
                'POST',
                formData
            )
                .then(function (data) {

                    const id =
                        data.id ||
                        formCancelar.querySelector(
                            'input[name="id"]'
                        )?.value;

                    if (data.membresia) {

                        actualizarMembresiaEnTabla(
                            data.membresia,
                            data.urls
                        );

                    } else {

                        const fila =
                            buscarFilaPorId(
                                id
                            );

                        if (fila) {

                            const datos =
                                obtenerDatosFila(
                                    fila
                                );

                            datos.estado =
                                'cancelada';

                            actualizarMembresiaEnTabla(
                                datos
                            );
                        }
                    }

                    cerrarModal(
                        modalCancelar
                    );

                    mostrarExito(
                        data.mensaje ||
                        'Membresía cancelada correctamente.'
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

            const botonEditar =
                evento.target.closest(
                    '.btn-editar-membresia'
                );

            if (botonEditar) {

                const id =
                    botonEditar.dataset.id ||
                    '';

                const nombre =
                    botonEditar.dataset.name ||
                    '—';

                const plan =
                    botonEditar.dataset.plan ||
                    '—';

                const estado =
                    botonEditar.dataset.estado ||
                    '—';

                const observaciones =
                    botonEditar.dataset.observaciones ||
                    '';

                const fechaInicio =
                    botonEditar.dataset.fechaInicio ||
                    '—';

                const fechaFin =
                    botonEditar.dataset.fechaFin ||
                    '—';

                const url =
                    botonEditar.dataset.url ||
                    '';

                const inputId =
                    document.getElementById(
                        'editar_id'
                    );

                const nombreElemento =
                    document.getElementById(
                        'editar_nombre'
                    );

                const planElemento =
                    document.getElementById(
                        'editar_plan'
                    );

                const estadoElemento =
                    document.getElementById(
                        'editar_estado'
                    );

                const periodoElemento =
                    document.getElementById(
                        'editar_periodo'
                    );

                const observacionesElemento =
                    document.getElementById(
                        'editar_observaciones'
                    );

                if (inputId) {
                    inputId.value =
                        id;
                }

                if (nombreElemento) {
                    nombreElemento.textContent =
                        nombre;
                }

                if (planElemento) {
                    planElemento.textContent =
                        plan;
                }

                if (estadoElemento) {
                    estadoElemento.textContent =
                        estado === 'activa'
                            ? 'Activa'
                            : estado === 'vencida'
                                ? 'Vencida'
                                : estado === 'cancelada'
                                    ? 'Cancelada'
                                    : estado;
                }

                if (periodoElemento) {
                    periodoElemento.textContent =
                        fechaInicio +
                        ' — ' +
                        fechaFin;
                }

                if (observacionesElemento) {
                    observacionesElemento.value =
                        observaciones;
                }

                observacionesOriginales =
                    String(observaciones || '').trim();

                if (formEditar) {
                    formEditar.action =
                        url;
                }

                if (modalEditar) {
                    bootstrap.Modal.getOrCreateInstance(
                        modalEditar
                    ).show();
                }

                return;
            }

            const botonRenovar =
                evento.target.closest(
                    '.btn-renovar-membresia'
                );

            if (botonRenovar) {

                const id =
                    botonRenovar.dataset.id ||
                    '';

                const nombre =
                    botonRenovar.dataset.name ||
                    '—';

                const plan =
                    botonRenovar.dataset.plan ||
                    '—';

                const fechaFin =
                    botonRenovar.dataset.fechaFin ||
                    '—';

                const url =
                    botonRenovar.dataset.url ||
                    '';

                const inputId =
                    document.getElementById(
                        'renovar_id'
                    );

                const nombreElemento =
                    document.getElementById(
                        'renovar_nombre'
                    );

                const planElemento =
                    document.getElementById(
                        'renovar_plan_actual'
                    );

                const planSelect =
                    document.getElementById(
                        'renovar_plan_id'
                    );

                const metodoPago =
                    document.getElementById(
                        'renovar_metodo_pago'
                    );

                const referencia =
                    document.getElementById(
                        'renovar_referencia'
                    );

                const montoRecibido =
                    document.getElementById(
                        'renovar_monto_recibido'
                    );

                const observaciones =
                    document.getElementById(
                        'renovar_observaciones'
                    );

                if (inputId) {
                    inputId.value =
                        id;
                }

                if (nombreElemento) {
                    nombreElemento.textContent =
                        nombre;
                }

                if (planElemento) {
                    planElemento.textContent =
                        plan +
                        ' — vence ' +
                        fechaFin;
                }

                if (planSelect) {
                    planSelect.value =
                        '';

                    planSelect.dispatchEvent(
                        new Event(
                            'change'
                        )
                    );
                }

                if (metodoPago) {
                    metodoPago.value =
                        '';

                    actualizarMetodoPagoRenovacion();
                }

                if (referencia) {
                    referencia.value =
                        '';
                }

                if (montoRecibido) {
                    montoRecibido.value =
                        '';
                }

                if (observaciones) {
                    observaciones.value =
                        '';
                }

                if (formRenovar) {
                    formRenovar.action =
                        url;
                }

                if (modalRenovar) {
                    bootstrap.Modal.getOrCreateInstance(
                        modalRenovar
                    ).show();
                }

                return;
            }

            const botonCancelar =
                evento.target.closest(
                    '.btn-cancelar-membresia'
                );

            if (!botonCancelar) {
                return;
            }

            const id =
                botonCancelar.dataset.id ||
                '';

            const nombre =
                botonCancelar.dataset.name ||
                'este miembro';

            const plan =
                botonCancelar.dataset.plan ||
                '—';

            const url =
                botonCancelar.dataset.url ||
                '';

            const inputId =
                document.getElementById(
                    'cancelar_id'
                );

            const nombreElemento =
                document.getElementById(
                    'cancelar_nombre'
                );

            const planElemento =
                document.getElementById(
                    'cancelar_plan'
                );

            if (inputId) {
                inputId.value =
                    id;
            }

            if (nombreElemento) {
                nombreElemento.textContent =
                    nombre;
            }

            if (planElemento) {
                planElemento.textContent =
                    plan;
            }

            if (formCancelar) {
                formCancelar.action =
                    url;
            }

            if (modalCancelar) {
                bootstrap.Modal.getOrCreateInstance(
                    modalCancelar
                ).show();
            }
        }
    );

    modalNuevo?.addEventListener(
        'show.bs.modal',
        function () {
            actualizarPrecioPlan();
            actualizarMetodoPagoNuevo();
        }
    );

    modalNuevo?.addEventListener(
        'hidden.bs.modal',
        function () {

            resetFormulario(
                formNuevo
            );

            actualizarPrecioPlan();
            actualizarMetodoPagoNuevo();
        }
    );

    modalEditar?.addEventListener(
        'hidden.bs.modal',
        function () {

            observacionesOriginales = null;

            resetFormulario(
                formEditar
            );

            const inputId =
                document.getElementById(
                    'editar_id'
                );

            const nombreElemento =
                document.getElementById(
                    'editar_nombre'
                );

            const planElemento =
                document.getElementById(
                    'editar_plan'
                );

            const estadoElemento =
                document.getElementById(
                    'editar_estado'
                );

            const periodoElemento =
                document.getElementById(
                    'editar_periodo'
                );

            if (inputId) {
                inputId.value =
                    '';
            }

            if (nombreElemento) {
                nombreElemento.textContent =
                    '—';
            }

            if (planElemento) {
                planElemento.textContent =
                    '—';
            }

            if (estadoElemento) {
                estadoElemento.textContent =
                    '—';
            }

            if (periodoElemento) {
                periodoElemento.textContent =
                    '—';
            }

            if (formEditar) {
                formEditar.action =
                    '';
            }
        }
    );

    modalRenovar?.addEventListener(
        'hidden.bs.modal',
        function () {

            resetFormulario(
                formRenovar
            );

            const inputId =
                document.getElementById(
                    'renovar_id'
                );

            const nombreElemento =
                document.getElementById(
                    'renovar_nombre'
                );

            const planElemento =
                document.getElementById(
                    'renovar_plan_actual'
                );

            const precioElemento =
                document.getElementById(
                    'precioRenovacion'
                );

            const periodoElemento =
                document.getElementById(
                    'renovar_periodo_info'
                );

            if (inputId) {
                inputId.value =
                    '';
            }

            if (nombreElemento) {
                nombreElemento.textContent =
                    '—';
            }

            if (planElemento) {
                planElemento.textContent =
                    '—';
            }

            if (precioElemento) {
                precioElemento.textContent =
                    '—';
            }

            if (periodoElemento) {
                periodoElemento.textContent =
                    'Selecciona un plan para calcular el nuevo periodo de la membresía.';
            }

            if (formRenovar) {
                formRenovar.action =
                    '';
            }

            actualizarMetodoPagoRenovacion();
        }
    );

    modalCancelar?.addEventListener(
        'hidden.bs.modal',
        function () {

            const inputId =
                document.getElementById(
                    'cancelar_id'
                );

            const nombreElemento =
                document.getElementById(
                    'cancelar_nombre'
                );

            const planElemento =
                document.getElementById(
                    'cancelar_plan'
                );

            if (inputId) {
                inputId.value =
                    '';
            }

            if (nombreElemento) {
                nombreElemento.textContent =
                    'este miembro';
            }

            if (planElemento) {
                planElemento.textContent =
                    '—';
            }

            if (formCancelar) {
                formCancelar.action =
                    '';
            }
        }
    );

    inicializarDataTable();
});
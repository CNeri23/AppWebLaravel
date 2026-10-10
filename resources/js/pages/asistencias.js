document.addEventListener('DOMContentLoaded', function () {
    const app = document.getElementById('asistenciasApp');

    if (!app) {
        return;
    }

    const csrfTokenElement = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenElement ? csrfTokenElement.content : '';
    const urlBuscar = app.dataset.urlBuscar || '';
    const urlRegistrar = app.dataset.urlRegistrar || '';
    const incluyeHoy = app.dataset.incluyeHoy === '1';
    const puedeAnular = app.dataset.puedeAnular === '1';
    const iconoAnular = app.dataset.iconoAnular || '<i class="fa-regular fa-trash-can"></i>';
    const formBuscar = document.getElementById('formBuscarAsistencia');
    const inputBusqueda = document.getElementById('asistenciaBusqueda');
    const listaResultados = document.getElementById('asistenciaResultados');
    const panelResultado = document.getElementById('asistenciaResultado');
    const kpiEntradas = document.getElementById('kpiEntradas');
    const kpiMiembros = document.getElementById('kpiMiembros');
    const kpiDenegadas = document.getElementById('kpiDenegadas');

    let dataTable = null;
    let temporizador = null;
    let numeroBusqueda = 0;
    let registrando = false;

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

    function avisar(tipo, mensaje) {
        if (window.showToast) {
            window.showToast(tipo, mensaje);
        }
    }

    function temaSwal() {
        const cuerpo = getComputedStyle(document.body);
        const referencia = document.querySelector('.modal-content');
        let fondo = referencia ? getComputedStyle(referencia).backgroundColor : cuerpo.backgroundColor;

        if (!fondo || fondo === 'transparent' || fondo === 'rgba(0, 0, 0, 0)') {
            fondo = cuerpo.backgroundColor;
        }
        return { background: fondo, color: cuerpo.color };
    }

    function confirmar(titulo, texto, textoConfirmar) {
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
            allowOutsideClick: false,
            allowEscapeKey: false,
            allowEnterKey: false,
            buttonsStyling: false,
            heightAuto: false,
            customClass: {
                popup: 'ip-confirmation-popup',
                icon: 'ip-confirmation-icon',
                title: 'ip-confirmation-title',
                actions: 'ip-confirmation-actions',
                confirmButton: 'btn btn-danger mx-1',
                cancelButton: 'btn btn-secondary mx-1'
            },
            didOpen: function (popup) {
                const contenedor = Swal.getContainer();

                if (contenedor) {
                    contenedor.addEventListener('click', function (evento) {
                        if (evento.target === contenedor) {
                            popup.animate(
                                [
                                    { transform: 'translateX(0)' },
                                    { transform: 'translateX(-8px)' },
                                    { transform: 'translateX(8px)' },
                                    { transform: 'translateX(-6px)' },
                                    { transform: 'translateX(6px)' },
                                    { transform: 'translateX(0)' }
                                ],
                                { duration: 300, easing: 'ease-in-out' }
                            );
                        }
                    }, true);
                }
            }
        }).then(function (resultado) {
            return resultado.isConfirmed;
        });
    }

    function peticion(url, opciones) {
        const config = Object.assign({
            credentials: 'same-origin',
            headers: {}
        }, opciones || {});

        config.headers = Object.assign({
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }, config.headers);

        return fetch(url, config).then(async function (response) {
            const data = await response.json().catch(function () {
                return {};
            });

            if (!response.ok || data.success === false) {
                const primerError = data.errors
                    ? Object.values(data.errors).flat()[0]
                    : null;

                throw new Error(primerError || data.mensaje || data.message || 'Ocurrió un error al procesar la solicitud.');
            }
            return data;
        });
    }

    function inicializarDataTable() {
        if (!window.DataTable) {
            return;
        }
        const columnas = puedeAnular ? 6 : 5;

        dataTable = new DataTable('#tablaAsistencias', {
            autoWidth: false,
            pageLength: 10,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            order: [
                [0, 'desc']
            ],
            columnDefs: puedeAnular
                ? [{
                    orderable: false,
                    searchable: false,
                    targets: columnas - 1
                }]
                : [],
            language: {
                search: 'Buscar:',
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Mostrando 0 a 0 de 0 registros',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron registros',
                emptyTable: 'No hay asistencias en este periodo',
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    previous: '<i class="fa-solid fa-angle-left"></i>',
                    next: '<i class="fa-solid fa-angle-right"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>'
                }
            },
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

    function crearFila(registro) {
        const fila = document.createElement('tr');

        fila.dataset.id = registro.id;
        fila.dataset.persona = registro.persona_id;
        fila.dataset.permitido = registro.permitido ? '1' : '0';

        const resultado = registro.permitido
            ? '<span class="badge rounded-pill text-bg-success">Permitido</span>'
            : '<span class="badge rounded-pill text-bg-danger">Denegado</span>' +
            (registro.motivo ? '<div class="small text-secondary mt-1">' + escapeHtml(registro.motivo) + '</div>' : '');

        const acciones = puedeAnular
            ? '<td class="text-center"><div class="asistencia-actions">' +
            '<button type="button" ' +
            'class="btn btn-sm btn-outline-danger asistencia-action-btn btn-anular-asistencia" ' +
            'title="Anular registro" ' +
            'data-url="' + escapeAttribute(registro.url_eliminar) + '" ' +
            'data-nombre="' + escapeAttribute(registro.nombre) + '">' + iconoAnular +
            '</button></div></td>'
            : '';

        fila.innerHTML =
            '<td data-order="' + escapeAttribute(registro.timestamp) + '">' +
            '<div class="fw-semibold">' + escapeHtml(registro.hora) + '</div>' +
            '<div class="small text-secondary">' + escapeHtml(registro.fecha) + '</div>' +
            '</td>' +
            '<td>' +
            '<div class="fw-semibold">' + escapeHtml(registro.nombre) + '</div>' +
            (registro.email ? '<div class="small text-secondary">' + escapeHtml(registro.email) + '</div>' : '') +
            '</td>' +
            '<td>' + escapeHtml(registro.plan || '—') + '</td>' +
            '<td>' + resultado + '</td>' +
            '<td>' + escapeHtml(registro.usuario || '—') + '</td>' + acciones;

        return fila;
    }

    function actualizarResumen() {
        if (!dataTable) {
            return;
        }

        let entradas = 0;
        let denegadas = 0;
        const miembros = new Set();

        dataTable.rows().every(function () {
            const fila = this.node();

            if (!fila) {
                return;
            }

            if (fila.dataset.permitido === '1') {
                entradas++;
                miembros.add(fila.dataset.persona);
            } else {
                denegadas++;
            }
        });

        if (kpiEntradas) {
            kpiEntradas.textContent = entradas;
        }

        if (kpiMiembros) {
            kpiMiembros.textContent = miembros.size;
        }

        if (kpiDenegadas) {
            kpiDenegadas.textContent = denegadas;
        }
    }

    function limpiarResultados() {
        if (!listaResultados) {
            return;
        }

        listaResultados.innerHTML = '';
        listaResultados.classList.add('d-none');
    }

    function ocultarPanelResultado() {
        if (!panelResultado) {
            return;
        }

        panelResultado.className = 'alert asistencia-resultado mt-3 mb-0 d-none';
        panelResultado.innerHTML = '';
    }

    function mostrarPanelResultado(tipo, titulo, miembro, detalle) {
        if (!panelResultado) {
            return;
        }

        const estilos = {
            permitido: { clase: 'alert-success', icono: 'fa-circle-check' },
            denegado: { clase: 'alert-danger', icono: 'fa-circle-xmark' },
            duplicada: { clase: 'alert-warning', icono: 'fa-circle-exclamation' }
        };

        const estilo = estilos[tipo] || estilos.duplicada;

        const lineas = [];

        if (miembro && miembro.plan) {
            lineas.push(
                'Plan: ' + escapeHtml(miembro.plan) +
                (miembro.vence ? ' · vence ' + escapeHtml(miembro.vence) : '')
            );
        }

        if (detalle) {
            lineas.push(escapeHtml(detalle));
        }

        panelResultado.className =
            'alert asistencia-resultado ' + estilo.clase + ' mt-3 mb-0 d-flex align-items-center gap-3';

        panelResultado.innerHTML =
            '<i class="fa-solid ' + estilo.icono + ' asistencia-resultado-icono"></i>' +
            '<div>' +
            '<div class="fw-bold fs-5">' + escapeHtml(titulo) + '</div>' +
            (miembro  ? '<div class="fw-semibold">' + escapeHtml(miembro.nombre) + '</div>' : '') +
            lineas.map(function (linea) {
                return '<div class="small">' + linea + '</div>';
            }).join('') +
            '</div>';
    }

    function etiquetaEstado(miembro) {
        const estados = {
            vigente: ['text-bg-success', 'Vigente'],
            vencida: ['text-bg-secondary', 'Vencida'],
            cancelada: ['text-bg-danger', 'Cancelada'],
            pendiente: ['text-bg-warning', 'No inicia'],
            sin_membresia: ['text-bg-secondary', 'Sin membresía']
        };
        const estado = estados[miembro.estado] || estados.sin_membresia;

        return '<span class="badge rounded-pill ' + estado[0] + '">' + estado[1] + '</span>';
    }

    function pintarResultados(miembros) {
        if (!listaResultados) {
            return;
        }

        if (!miembros.length) {
            listaResultados.innerHTML =
                '<div class="list-group-item text-secondary">' +
                'No se encontraron miembros con esa búsqueda.' +
                '</div>';
            listaResultados.classList.remove('d-none');

            return;
        }

        listaResultados.innerHTML = miembros.map(function (miembro) {
            const detalle = [
                'No. ' + miembro.id,
                miembro.plan,
                miembro.vence ? 'vence ' + miembro.vence : null,
                miembro.telefono
            ].filter(Boolean).map(escapeHtml).join(' · ');

            return '<button type="button" ' +
                'class="list-group-item list-group-item-action d-flex justify-content-between align-items-center gap-3" ' +
                'data-id="' + escapeAttribute(miembro.id) + '">' +
                '<span>' +
                '<span class="d-block fw-semibold">' + escapeHtml(miembro.nombre) + '</span>' +
                '<span class="d-block small text-secondary">' + detalle + '</span>' +
                '</span>' + etiquetaEstado(miembro) +
                '</button>';
        }).join('');

        listaResultados.classList.remove('d-none');
    }

    function buscar(registrarSiUnico) {
        const texto = inputBusqueda.value.trim();

        if (!texto) {
            numeroBusqueda++;
            limpiarResultados();
            return Promise.resolve();
        }
        const esta = ++numeroBusqueda;

        return peticion(urlBuscar + '?q=' + encodeURIComponent(texto))
            .then(function (data) {
                if (esta !== numeroBusqueda) {
                    return;
                }

                const miembros = data.miembros || [];

                if (registrarSiUnico && miembros.length === 1) {
                    limpiarResultados();
                    registrar(miembros[0].id);

                    return;
                }

                pintarResultados(miembros);
            })
            .catch(function (error) {
                avisar('error', error.message);
            });
    }

    function registrar(personaId) {
        if (registrando) {
            return;
        }

        registrando = true;

        const formData = new FormData();
        formData.append('persona_id', personaId);

        listaResultados
            .querySelectorAll('button')
            .forEach(function (boton) {
                boton.disabled = true;
            });

        peticion(urlRegistrar, {
            method: 'POST',
            body: formData
        })
            .then(function (data) {
                const titulos = {
                    permitido: 'Acceso permitido',
                    denegado: 'Acceso denegado',
                    duplicada: 'Ya registrado hoy'
                };

                mostrarPanelResultado(
                    data.resultado,
                    titulos[data.resultado] || 'Resultado',
                    data.miembro,
                    data.resultado === 'permitido' ? null : data.mensaje
                );

                if (data.asistencia && dataTable && incluyeHoy) {
                    dataTable.row.add(crearFila(data.asistencia)).draw(false);
                    actualizarResumen();
                }

                inputBusqueda.value = '';
                limpiarResultados();
            })
            .catch(function (error) {
                ocultarPanelResultado();
                avisar('error', error.message);

                listaResultados
                    .querySelectorAll('button')
                    .forEach(function (boton) {
                        boton.disabled = false;
                    });
            })
            .finally(function () {
                registrando = false;
                inputBusqueda.focus();
            });
    }

    if (formBuscar && inputBusqueda && listaResultados) {
        inputBusqueda.addEventListener('input', function () {
            ocultarPanelResultado();
            clearTimeout(temporizador);

            if (inputBusqueda.value.trim().length < 2) {
                numeroBusqueda++;
                limpiarResultados();

                return;
            }

            temporizador = setTimeout(function () {
                buscar(false);
            }, 250);
        });

        formBuscar.addEventListener('submit', function (evento) {
            evento.preventDefault();
            clearTimeout(temporizador);
            ocultarPanelResultado();
            buscar(true);
        });

        listaResultados.addEventListener('click', function (evento) {
            const boton = evento.target.closest('button[data-id]');

            if (boton) {
                registrar(boton.dataset.id);
            }
        });
    }

    document.getElementById('tablaAsistencias').addEventListener('click', function (evento) {
        const boton = evento.target.closest('.btn-anular-asistencia');

        if (!boton) {
            return;
        }

        const fila = boton.closest('tr');
        const nombre = boton.dataset.nombre || 'este miembro';

        confirmar(
            '¿Anular registro?',
            'Se eliminará el registro de ' + nombre + '. Esta acción no se puede deshacer.',
            'Anular'
        ).then(function (confirmado) {
            if (!confirmado) {
                return;
            }
            boton.disabled = true;

            peticion(boton.dataset.url, {
                method: 'DELETE'
            })
                .then(function (data) {
                    if (dataTable) {
                        dataTable.row(fila).remove().draw(false);
                        actualizarResumen();
                    }
                    avisar('success', data.mensaje || 'Registro anulado correctamente.');
                })
                .catch(function (error) {
                    boton.disabled = false;
                    avisar('error', error.message);
                });
        });
    });
    inicializarDataTable();

    if (inputBusqueda) {
        inputBusqueda.focus();
    }
});
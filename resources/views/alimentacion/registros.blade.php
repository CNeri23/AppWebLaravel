@extends('layouts.app')

@section('content')
@php
    $accionesRegistros = $accionesRegistros ?? collect();
    $puedeCrear = $accionesRegistros->contains('slug', 'registro-comidas.crear');
    $puedeEliminar = $accionesRegistros->contains('slug', 'registro-comidas.eliminar');
    $accionCrearRegistro = $accionesRegistros->firstWhere('slug', 'registro-comidas.crear');
    $accionEliminarRegistro = $accionesRegistros->firstWhere('slug', 'registro-comidas.eliminar');
@endphp

<div class="card border-0 shadow-sm usuarios-header-card mb-4">
    <div class="card-body usuarios-header-content">
        <div>
            <h2 class="fw-bold mb-1">Registro de comidas</h2>
            <p class="text-secondary mb-0">Registra tus comidas y consulta sus calorías y macronutrientes.</p>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <form method="GET" class="d-flex align-items-center gap-2">
                <label class="form-label mb-0" for="filtroFecha">Fecha</label>
                <input id="filtroFecha" type="date" class="form-control" name="fecha" value="{{ $fecha }}">
                <button class="btn btn-outline-primary" type="submit" data-tooltip="Consultar fecha"><i class="fa-solid fa-magnifying-glass me-1"></i>Consultar</button>
            </form>
            @if ($puedeCrear && $alimentos->isNotEmpty())
            <button type="button" class="btn btn-primary usuarios-header-action" data-bs-toggle="modal" data-bs-target="#modalRegistrarComida">
                {!! $accionCrearRegistro->icono ?: '<i class="fa-solid fa-plus me-2"></i>' !!}
                {{ $accionCrearRegistro->nombre }}
            </button>
            @endif
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach (['calorias' => ['Calorías','kcal','fa-fire-flame-curved'], 'proteinas' => ['Proteínas','g','fa-drumstick-bite'], 'carbohidratos' => ['Carbohidratos','g','fa-bread-slice'], 'grasas' => ['Grasas','g','fa-droplet']] as $key => $info)
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between text-secondary"><span>{{ $info[0] }}</span><i class="fa-solid {{ $info[2] }} text-primary"></i></div>
                <div class="fs-4 fw-bold mt-2">{{ number_format((float) $totales[$key], 1) }} <small class="fs-6 text-secondary">{{ $info[1] }}</small></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

@if ($alimentos->isEmpty())
<div class="alert alert-info d-flex align-items-center gap-2" role="status">
    <i class="fa-solid fa-circle-info"></i>
    <span>Primero registra alimentos en el <a href="{{ route('alimentacion.alimentos.index') }}" class="alert-link">catálogo de alimentos</a>.</span>
</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent border-0 pt-4 px-4">
        <h5 class="fw-semibold mb-0">Comidas del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaRegistrosComida" class="table table-hover align-middle mb-0 w-100">
                <thead><tr><th class="ps-4">Comida</th><th>Notas</th><th>Alimentos</th><th>Calorías</th><th>Proteínas</th><th>Carbohidratos</th><th>Grasas</th><th class="text-center pe-4">Acciones</th></tr></thead>
                <tbody>
                @foreach ($registros as $registro)<tr data-id="{{ $registro->id }}">
                        <td class="ps-4"><span class="fw-semibold">{{ ucfirst($registro->tipo_comida) }}</span><small class="d-block text-secondary">{{ $registro->created_at?->format('H:i') }}</small></td>
                        <td>{{ $registro->notas ?: '—' }}</td>
                        <td>{{ $registro->detalles->count() }}</td>
                        <td>{{ number_format((float) $registro->detalles->sum('calorias'), 1) }} kcal</td>
                        <td>{{ number_format((float) $registro->detalles->sum('proteinas'), 1) }} g</td>
                        <td>{{ number_format((float) $registro->detalles->sum('carbohidratos'), 1) }} g</td>
                        <td>{{ number_format((float) $registro->detalles->sum('grasas'), 1) }} g</td>
                        <td class="text-center px-4">
                            <div class="usuario-actions">
                                <button type="button" class="btn btn-sm btn-outline-info usuario-action-btn" data-tooltip="Ver alimentos"
                                    data-bs-toggle="modal" data-bs-target="#modalDetalleComida"
                                    data-tipo="{{ ucfirst($registro->tipo_comida) }}" data-notas="{{ $registro->notas }}"
                                    data-detalles="{{ json_encode($registro->detalles->map(fn ($d) => ['nombre' => $d->nombre_alimento, 'cantidad' => $d->cantidad, 'unidad' => $d->unidad, 'calorias' => $d->calorias, 'proteinas' => $d->proteinas, 'carbohidratos' => $d->carbohidratos, 'grasas' => $d->grasas])->values()) }}">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @if ($puedeEliminar)
                                <button type="button" class="btn btn-sm btn-outline-danger usuario-action-btn" data-tooltip="{{ $accionEliminarRegistro->nombre }}"
                                    data-bs-toggle="modal" data-bs-target="#modalEliminarRegistro"
                                    data-url="{{ route('alimentacion.registros.destroy', $registro) }}" data-tipo="{{ ucfirst($registro->tipo_comida) }}">
                                    {!! $accionEliminarRegistro->icono ?: '<i class="fa-solid fa-trash"></i>' !!}
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@if ($puedeCrear && $alimentos->isNotEmpty())
<div class="modal fade" id="modalRegistrarComida" tabindex="-1" aria-labelledby="modalRegistrarComidaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-semibold" id="modalRegistrarComidaLabel"><i class="fa-solid fa-utensils me-2"></i>Registrar comida</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <form method="POST" action="{{ route('alimentacion.registros.store') }}" id="formRegistroComida" novalidate autocomplete="off">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="fecha" value="{{ $fecha }}">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4"><label class="form-label" for="tipo_comida">Tipo de comida</label><select class="form-select" id="tipo_comida" name="tipo_comida" required><option value="desayuno">Desayuno</option><option value="comida">Comida</option><option value="cena">Cena</option><option value="colacion">Colación</option></select><div class="invalid-feedback" data-error-for="tipo_comida"></div></div>
                        <div class="col-md-8"><label class="form-label" for="notas_comida">Notas (opcional)</label><input class="form-control" id="notas_comida" name="notas" maxlength="2000" placeholder="Ej. comida después del entrenamiento"><div class="invalid-feedback" data-error-for="notas"></div></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h6 class="fw-semibold mb-0">Alimentos consumidos</h6><button type="button" class="btn btn-sm btn-outline-primary" id="agregarAlimento"><i class="fa-solid fa-plus me-1"></i>Agregar alimento</button></div>
                    <div id="filasAlimentos" class="d-grid gap-3">
                        <div class="row g-2 align-items-end fila-alimento">
                            <div class="col-md-6"><label class="form-label">Alimento</label><select class="form-select alimento-select" name="alimentos[0][id]" required>@foreach ($alimentos as $alimento)<option value="{{ $alimento->id }}" data-unidad="{{ $alimento->unidad_base }}" data-porcion="{{ $alimento->nombre_porcion && $alimento->cantidad_porcion ? '1' : '0' }}">{{ $alimento->nombre }} ({{ $alimento->unidad_base }})</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label">Cantidad</label><input class="form-control" type="number" name="alimentos[0][cantidad]" min="0.01" step="0.01" value="100" required></div>
                            <div class="col-md-2"><label class="form-label">Unidad</label><select class="form-select unidad-select" name="alimentos[0][unidad]" required><option value="g">Gramos</option><option value="ml">Mililitros</option><option value="porcion">Porciones</option></select></div>
                            <div class="col-md-1"><button type="button" class="btn btn-outline-danger quitar-fila" data-tooltip="Quitar alimento" disabled><i class="fa-solid fa-xmark"></i></button></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar comida</button></div>
            </form>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="modalDetalleComida" tabindex="-1" aria-labelledby="modalDetalleComidaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold" id="modalDetalleComidaLabel"><i class="fa-solid fa-list-check me-2"></i>Detalle de comida</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <div class="modal-body"><h6 id="detalleTipoComida" class="fw-semibold"></h6><p id="detalleNotasComida" class="text-secondary"></p><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Alimento</th><th>Cantidad</th><th>Calorías</th><th>Proteínas</th><th>Carbohidratos</th><th>Grasas</th></tr></thead><tbody id="detalleTablaComida"></tbody></table></div></div>
        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
    </div></div>
</div>

@if ($puedeEliminar)
<div class="modal fade" id="modalEliminarRegistro" tabindex="-1" aria-labelledby="modalEliminarRegistroLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header"><h5 class="modal-title fw-semibold" id="modalEliminarRegistroLabel"><i class="fa-solid fa-trash text-danger me-2"></i>Eliminar registro</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
        <form method="POST" id="formEliminarRegistro" novalidate>@csrf @method('DELETE')
            <div class="modal-body text-center"><div class="mx-auto mb-3 rounded-circle bg-danger-subtle text-danger d-flex align-items-center justify-content-center" style="width:58px;height:58px"><i class="fa-solid fa-trash fa-lg"></i></div><p class="mb-0">¿Eliminar el registro de <strong id="eliminarTipoRegistro">esta comida</strong>? Esta acción no se puede deshacer.</p></div>
            <div class="modal-footer justify-content-center"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-danger"><i class="fa-solid fa-trash me-2"></i>Eliminar</button></div>
        </form>
    </div></div>
</div>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('filasAlimentos');
    const addButton = document.getElementById('agregarAlimento');
    let index = 1;
    const foods = @json($alimentos->map(fn ($a) => ['id' => $a->id, 'nombre' => $a->nombre, 'unidad' => $a->unidad_base, 'porcion' => (bool) ($a->nombre_porcion && $a->cantidad_porcion)])->values());

    function configureUnit(row) {
        const food = row.querySelector('.alimento-select');
        const unit = row.querySelector('.unidad-select');
        if (!food || !unit) return;
        const selected = food.selectedOptions[0];
        Array.from(unit.options).forEach(option => {
            option.disabled = option.value === 'porcion' ? selected.dataset.porcion !== '1' : option.value !== selected.dataset.unidad;
        });
        if (unit.selectedOptions[0]?.disabled) unit.value = selected.dataset.unidad;
    }
    function refreshRemoveButtons() {
        if (!container) return;
        const rows = container.querySelectorAll('.fila-alimento');
        rows.forEach(row => { row.querySelector('.quitar-fila').disabled = rows.length === 1; });
    }
    if (container && addButton) {
        const first = container.querySelector('.fila-alimento');
        first.querySelector('.alimento-select').addEventListener('change', () => configureUnit(first));
        configureUnit(first);
        addButton.addEventListener('click', function () {
            const row = document.createElement('div');
            row.className = 'row g-2 align-items-end fila-alimento';
            const foodSelect = document.createElement('select');
            foodSelect.className = 'form-select alimento-select';
            foodSelect.name = 'alimentos[' + index + '][id]';
            foodSelect.required = true;
            foods.forEach(food => {
                const option = document.createElement('option');
                option.value = food.id;
                option.textContent = food.nombre + ' (' + food.unidad + ')';
                option.dataset.unidad = food.unidad;
                option.dataset.porcion = food.porcion ? '1' : '0';
                foodSelect.appendChild(option);
            });
            const foodCol = document.createElement('div'); foodCol.className = 'col-md-6';
            const foodLabel = document.createElement('label'); foodLabel.className = 'form-label'; foodLabel.textContent = 'Alimento'; foodCol.append(foodLabel, foodSelect);
            const amountCol = document.createElement('div'); amountCol.className = 'col-md-3';
            const amountLabel = document.createElement('label'); amountLabel.className = 'form-label'; amountLabel.textContent = 'Cantidad';
            const amount = document.createElement('input'); amount.className = 'form-control'; amount.type = 'number'; amount.name = 'alimentos[' + index + '][cantidad]'; amount.min = '0.01'; amount.step = '0.01'; amount.value = '100'; amount.required = true; amountLabel.htmlFor = amount.name; amountCol.append(amountLabel, amount);
            const unitCol = document.createElement('div'); unitCol.className = 'col-md-2';
            const unitLabel = document.createElement('label'); unitLabel.className = 'form-label'; unitLabel.textContent = 'Unidad';
            const unitSelect = document.createElement('select'); unitSelect.className = 'form-select unidad-select'; unitSelect.name = 'alimentos[' + index + '][unidad]'; unitSelect.required = true;
            [['g','Gramos'],['ml','Mililitros'],['porcion','Porciones']].forEach(([value,label]) => { const option = document.createElement('option'); option.value = value; option.textContent = label; unitSelect.appendChild(option); });
            unitCol.append(unitLabel, unitSelect);
            const removeCol = document.createElement('div'); removeCol.className = 'col-md-1';
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn-outline-danger quitar-fila'; remove.dataset.tooltip = 'Quitar alimento'; remove.innerHTML = '<i class="fa-solid fa-xmark"></i>'; remove.addEventListener('click', () => { row.remove(); refreshRemoveButtons(); }); removeCol.appendChild(remove);
            row.append(foodCol, amountCol, unitCol, removeCol);
            container.appendChild(row); index++;
            foodSelect.addEventListener('change', () => configureUnit(row));
            configureUnit(row); refreshRemoveButtons();
        });
    }

    document.getElementById('modalEliminarRegistro')?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('formEliminarRegistro').action = button.dataset.url;
        document.getElementById('eliminarTipoRegistro').textContent = button.dataset.tipo;
    });

    document.getElementById('modalDetalleComida')?.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        document.getElementById('detalleTipoComida').textContent = button.dataset.tipo;
        document.getElementById('detalleNotasComida').textContent = button.dataset.notas || 'Sin notas';
        const tbody = document.getElementById('detalleTablaComida');
        tbody.replaceChildren();
        let details = [];
        try { details = JSON.parse(button.dataset.detalles || '[]'); } catch (e) {}
        details.forEach(item => {
            const tr = document.createElement('tr');
            [item.nombre, item.cantidad + ' ' + item.unidad, Number(item.calorias).toFixed(1) + ' kcal', Number(item.proteinas).toFixed(1) + ' g', Number(item.carbohidratos).toFixed(1) + ' g', Number(item.grasas).toFixed(1) + ' g'].forEach(value => {
                const td = document.createElement('td'); td.textContent = value; tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
    });
    @if (session('success'))
        if (typeof window.showToast === 'function') window.showToast('success', @json(session('success')));
    @endif
    @if ($errors->any())
        if (typeof window.showToast === 'function') window.showToast('error', @json($errors->first()));
    @endif
});
</script>
@endpush
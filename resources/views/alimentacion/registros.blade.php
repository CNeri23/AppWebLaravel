@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm mb-4"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h2 class="fw-bold mb-1">Registro de comidas</h2><p class="text-secondary mb-0">Registra tus comidas y calcula automáticamente sus nutrientes.</p></div><form method="GET" class="d-flex gap-2"><input type="date" class="form-control" name="fecha" value="{{ $fecha }}"><button class="btn btn-outline-primary">Consultar</button></form></div></div>
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if ($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

<div class="row g-3 mb-4">
@foreach (['calorias' => ['Calorías','kcal','fa-fire-flame-curved'],'proteinas' => ['Proteínas','g','fa-drumstick-bite'],'carbohidratos' => ['Carbohidratos','g','fa-bread-slice'],'grasas' => ['Grasas','g','fa-droplet']] as $key => $info)
<div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="text-secondary small">{{ $info[0] }}</div><div class="fs-4 fw-bold">{{ number_format($totales[$key], 1) }} <small class="fs-6 text-secondary">{{ $info[1] }}</small></div></div></div></div>
@endforeach
</div>

@if (($accionesRegistros ?? collect())->contains('registro-comidas.crear'))
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">Registrar comida</h5></div><div class="card-body px-4">
@if($alimentos->isEmpty())
<div class="alert alert-info mb-0">Primero agrega alimentos al <a href="{{ route('alimentacion.alimentos.index') }}">catálogo de alimentos</a>.</div>
@else
<form method="POST" action="{{ route('alimentacion.registros.store') }}" id="formRegistroComida">@csrf
<input type="hidden" name="fecha" value="{{ $fecha }}">
<div class="row g-3 mb-3"><div class="col-md-4"><label class="form-label">Tipo de comida</label><select class="form-select" name="tipo_comida" required><option value="desayuno">Desayuno</option><option value="comida">Comida</option><option value="cena">Cena</option><option value="colacion">Colación</option></select></div><div class="col-md-8"><label class="form-label">Notas (opcional)</label><input class="form-control" name="notas" maxlength="2000" placeholder="Ej. comida después del entrenamiento"></div></div>
<div id="filasAlimentos" class="d-grid gap-2">
<div class="row g-2 align-items-end fila-alimento">
<div class="col-md-5"><label class="form-label">Alimento</label><select class="form-select" name="alimentos[0][id]" required>@foreach($alimentos as $alimento)<option value="{{ $alimento->id }}" data-unidad="{{ $alimento->unidad_base }}" data-porcion="{{ $alimento->nombre_porcion ? '1' : '0' }}">{{ $alimento->nombre }} ({{ $alimento->unidad_base }})</option>@endforeach</select></div>
<div class="col-md-3"><label class="form-label">Cantidad</label><input class="form-control" type="number" name="alimentos[0][cantidad]" min="0.01" step="0.01" required value="100"></div>
<div class="col-md-3"><label class="form-label">Unidad</label><select class="form-select" name="alimentos[0][unidad]" required><option value="g">Gramos</option><option value="ml">Mililitros</option><option value="porcion">Porciones</option></select></div>
<div class="col-md-1"><button type="button" class="btn btn-outline-danger quitar-fila" title="Quitar alimento" disabled><i class="fa-solid fa-xmark"></i></button></div>
</div>
</div>
<div class="d-flex flex-wrap gap-2 mt-3"><button type="button" class="btn btn-outline-primary" id="agregarAlimento"><i class="fa-solid fa-plus me-2"></i>Agregar alimento</button><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i>Guardar comida</button></div>
</form>
@endif
</div></div>
@endif

<div class="card border-0 shadow-sm"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">Comidas del {{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</h5></div><div class="card-body">
@forelse($registros as $registro)
<div class="border rounded-3 p-3 mb-3"><div class="d-flex flex-wrap justify-content-between gap-2"><div><h6 class="fw-bold mb-1">{{ ucfirst($registro->tipo_comida) }}</h6><div class="text-secondary small">{{ $registro->notas ?: 'Sin notas' }}</div></div>@if (($accionesRegistros ?? collect())->contains('registro-comidas.eliminar'))<form method="POST" action="{{ route('alimentacion.registros.destroy', $registro) }}" onsubmit="return confirm('¿Eliminar este registro de comida?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash me-1"></i>Eliminar</button></form>@endif</div>
<div class="table-responsive mt-3"><table class="table table-sm align-middle mb-0"><thead><tr><th>Alimento</th><th>Cantidad</th><th>Calorías</th><th>Proteínas</th><th>Carbohidratos</th><th>Grasas</th></tr></thead><tbody>@foreach($registro->detalles as $detalle)<tr><td>{{ $detalle->nombre_alimento }}</td><td>{{ $detalle->cantidad }} {{ $detalle->unidad }}</td><td>{{ number_format($detalle->calorias,1) }}</td><td>{{ number_format($detalle->proteinas,1) }} g</td><td>{{ number_format($detalle->carbohidratos,1) }} g</td><td>{{ number_format($detalle->grasas,1) }} g</td></tr>@endforeach</tbody></table></div></div>
@empty<div class="text-center text-secondary py-4">No hay comidas registradas para esta fecha.</div>@endforelse
</div></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const contenedor = document.getElementById('filasAlimentos');
    const boton = document.getElementById('agregarAlimento');
    if (!contenedor || !boton) return;
    let indice = 1;
    const opciones = @json($alimentos->map(fn($a) => ['id'=>$a->id,'nombre'=>$a->nombre,'unidad'=>$a->unidad_base,'porcion'=>(bool)($a->nombre_porcion && $a->cantidad_porcion)])->values());
    boton.addEventListener('click', () => {
        const fila = document.createElement('div');
        fila.className = 'row g-2 align-items-end fila-alimento';
        const options = opciones.map(a => '<option value="'+a.id+'" data-unidad="'+a.unidad+'" data-porcion="'+(a.porcion?'1':'0')+'">'+String(a.nombre).replaceAll('&','&amp;').replaceAll('<','&lt;')+' ('+a.unidad+')</option>').join('');
        fila.innerHTML = '<div class="col-md-5"><label class="form-label">Alimento</label><select class="form-select" name="alimentos['+indice+'][id]" required>'+options+'</select></div><div class="col-md-3"><label class="form-label">Cantidad</label><input class="form-control" type="number" name="alimentos['+indice+'][cantidad]" min="0.01" step="0.01" required value="100"></div><div class="col-md-3"><label class="form-label">Unidad</label><select class="form-select" name="alimentos['+indice+'][unidad]" required><option value="g">Gramos</option><option value="ml">Mililitros</option><option value="porcion">Porciones</option></select></div><div class="col-md-1"><button type="button" class="btn btn-outline-danger quitar-fila" title="Quitar alimento"><i class="fa-solid fa-xmark"></i></button></div>';
        contenedor.appendChild(fila); indice++;
        fila.querySelector('.quitar-fila').addEventListener('click', () => fila.remove());
        fila.querySelector('select[name$="[id]"]').addEventListener('change', e => ajustarUnidad(fila, e.target));
        ajustarUnidad(fila, fila.querySelector('select[name$="[id]"]'));
    });
    function ajustarUnidad(fila, alimentoSelect) {
        const opt = alimentoSelect.selectedOptions[0], unidad = fila.querySelector('select[name$="[unidad]"]');
        [...unidad.options].forEach(o => o.disabled = (o.value !== 'porcion' && o.value !== opt.dataset.unidad) || (o.value === 'porcion' && opt.dataset.porcion !== '1'));
        if (unidad.selectedOptions[0]?.disabled) unidad.value = opt.dataset.unidad;
    }
    const primera = contenedor.querySelector('.fila-alimento');
    primera.querySelector('select[name$="[id]"]').addEventListener('change', e => ajustarUnidad(primera, e.target));
    ajustarUnidad(primera, primera.querySelector('select[name$="[id]"]'));
});
</script>
@endsection

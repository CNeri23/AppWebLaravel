@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="fw-bold mb-1">Catálogo de alimentos</h2>
            <p class="text-secondary mb-0">Administra los valores nutricionales por cada 100 g o 100 ml.</p>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">Agregar alimento</h5></div>
    <div class="card-body px-4">
        <form method="POST" action="{{ route('alimentacion.alimentos.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required maxlength="150" value="{{ old('nombre') }}"></div>
            <div class="col-md-3"><label class="form-label">Categoría</label><input name="categoria" class="form-control" maxlength="80" value="{{ old('categoria') }}" placeholder="Proteína, fruta, cereal..."></div>
            <div class="col-md-2"><label class="form-label">Unidad base</label><select name="unidad_base" class="form-select" required><option value="g">Gramos (g)</option><option value="ml">Mililitros (ml)</option></select></div>
            <div class="col-md-3"><label class="form-label">Nombre de porción</label><input name="nombre_porcion" class="form-control" maxlength="80" placeholder="1 pieza, 1 taza..."></div>
            <div class="col-md-3"><label class="form-label">Cantidad de porción (g/ml)</label><input name="cantidad_porcion" type="number" min="0.01" step="0.01" class="form-control" placeholder="Ej. 120"></div>
            <div class="col-md-2"><label class="form-label">Calorías</label><input name="calorias" type="number" min="0" step="0.01" class="form-control" value="0" required></div>
            <div class="col-md-2"><label class="form-label">Proteínas (g)</label><input name="proteinas" type="number" min="0" step="0.01" class="form-control" value="0" required></div>
            <div class="col-md-2"><label class="form-label">Carbohidratos (g)</label><input name="carbohidratos" type="number" min="0" step="0.01" class="form-control" value="0" required></div>
            <div class="col-md-2"><label class="form-label">Grasas (g)</label><input name="grasas" type="number" min="0" step="0.01" class="form-control" value="0" required></div>
            <div class="col-12"><button class="btn btn-primary"><i class="fa-solid fa-plus me-2"></i>Guardar alimento</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th class="ps-4">Alimento</th><th>Categoría</th><th>Base / porción</th><th>Calorías</th><th>Proteína</th><th>Carbohidratos</th><th>Grasas</th><th>Estado</th><th class="text-end pe-4">Acciones</th></tr></thead>
                <tbody>
                @forelse ($alimentos as $alimento)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $alimento->nombre }}</td><td>{{ $alimento->categoria ?: '—' }}</td>
                        <td>{{ $alimento->unidad_base }} @if($alimento->nombre_porcion)<span class="text-secondary d-block small">{{ $alimento->nombre_porcion }} = {{ $alimento->cantidad_porcion }} {{ $alimento->unidad_base }}</span>@endif</td>
                        <td>{{ number_format($alimento->calorias, 1) }} kcal</td><td>{{ number_format($alimento->proteinas, 1) }} g</td><td>{{ number_format($alimento->carbohidratos, 1) }} g</td><td>{{ number_format($alimento->grasas, 1) }} g</td>
                        <td><span class="badge {{ $alimento->activo ? 'bg-success-subtle text-success' : 'bg-secondary-subtle text-secondary' }}">{{ $alimento->activo ? 'Activo' : 'Inactivo' }}</span></td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editarAlimento{{ $alimento->id }}" aria-expanded="false" title="Editar"><i class="fa-solid fa-pen"></i></button>
                            <form class="d-inline" method="POST" action="{{ route('alimentacion.alimentos.destroy', $alimento) }}" onsubmit="return confirm('¿Eliminar o desactivar este alimento?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="fa-solid fa-trash"></i></button></form>
                        </td>
                    </tr>
                    <tr class="collapse" id="editarAlimento{{ $alimento->id }}"><td colspan="9" class="p-4">
                        <form method="POST" action="{{ route('alimentacion.alimentos.update', $alimento) }}" class="row g-3">@csrf @method('PUT')
                            <div class="col-md-3"><label class="form-label">Nombre</label><input name="nombre" class="form-control" required value="{{ $alimento->nombre }}"></div>
                            <div class="col-md-2"><label class="form-label">Categoría</label><input name="categoria" class="form-control" value="{{ $alimento->categoria }}"></div>
                            <div class="col-md-2"><label class="form-label">Unidad base</label><select name="unidad_base" class="form-select"><option value="g" @selected($alimento->unidad_base === 'g')>Gramos</option><option value="ml" @selected($alimento->unidad_base === 'ml')>Mililitros</option></select></div>
                            <div class="col-md-2"><label class="form-label">Nombre porción</label><input name="nombre_porcion" class="form-control" value="{{ $alimento->nombre_porcion }}"></div>
                            <div class="col-md-2"><label class="form-label">Cantidad porción</label><input name="cantidad_porcion" type="number" min="0.01" step="0.01" class="form-control" value="{{ $alimento->cantidad_porcion }}"></div>
                            <div class="col-md-2"><label class="form-label">Calorías</label><input name="calorias" type="number" min="0" step="0.01" class="form-control" required value="{{ $alimento->calorias }}"></div>
                            <div class="col-md-2"><label class="form-label">Proteínas</label><input name="proteinas" type="number" min="0" step="0.01" class="form-control" required value="{{ $alimento->proteinas }}"></div>
                            <div class="col-md-2"><label class="form-label">Carbohidratos</label><input name="carbohidratos" type="number" min="0" step="0.01" class="form-control" required value="{{ $alimento->carbohidratos }}"></div>
                            <div class="col-md-2"><label class="form-label">Grasas</label><input name="grasas" type="number" min="0" step="0.01" class="form-control" required value="{{ $alimento->grasas }}"></div>
                            <div class="col-md-2"><label class="form-label">Estado</label><select name="activo" class="form-select"><option value="1" @selected($alimento->activo)>Activo</option><option value="0" @selected(!$alimento->activo)>Inactivo</option></select></div>
                            <div class="col-12"><button class="btn btn-primary">Guardar cambios</button></div>
                        </form>
                    </td></tr>
                @empty
                    <tr><td colspan="9" class="text-center text-secondary py-5">Todavía no hay alimentos registrados.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

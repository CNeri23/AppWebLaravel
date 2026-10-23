@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h2 class="fw-bold mb-1">Panel nutricional</h2>
            <p class="text-secondary mb-0">Resumen de calorías y macronutrientes registrados por día.</p>
        </div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="form-label mb-0" for="panelFecha">Fecha</label>
            <input id="panelFecha" type="date" class="form-control" name="fecha" value="{{ $fecha }}">
            <button type="submit" class="btn btn-outline-primary" data-tooltip="Consultar fecha"><i class="fa-solid fa-magnifying-glass me-1"></i>Consultar</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    @foreach (['calorias' => ['Calorías','kcal','fa-fire-flame-curved'], 'proteinas' => ['Proteínas','g','fa-drumstick-bite'], 'carbohidratos' => ['Carbohidratos','g','fa-bread-slice'], 'grasas' => ['Grasas','g','fa-droplet']] as $key => $info)
    <div class="col-6 col-xl-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between text-secondary"><span>{{ $info[0] }}</span><i class="fa-solid {{ $info[2] }} text-primary"></i></div>
                <div class="fs-3 fw-bold mt-2">{{ number_format((float) $totales[$key], 1) }} <small class="fs-6 text-secondary">{{ $info[1] }}</small></div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent border-0 pt-4 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <h5 class="fw-semibold mb-0">Consumo por comida</h5>
        <span class="text-secondary small">{{ \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') }}</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table id="tablaPanelNutricional" class="table table-hover align-middle mb-0 w-100">
                <thead><tr><th class="ps-4">Comida</th><th>Alimentos registrados</th><th>Calorías</th><th>Proteínas</th><th>Carbohidratos</th><th class="pe-4">Grasas</th></tr></thead>
                <tbody>
                    @forelse ($registros as $registro)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ ucfirst($registro->tipo_comida) }}<small class="d-block text-secondary fw-normal">{{ $registro->created_at?->format('H:i') }}</small></td>
                        <td>{{ $registro->detalles->count() }}</td>
                        <td>{{ number_format((float) $registro->detalles->sum('calorias'), 1) }} kcal</td>
                        <td>{{ number_format((float) $registro->detalles->sum('proteinas'), 1) }} g</td>
                        <td>{{ number_format((float) $registro->detalles->sum('carbohidratos'), 1) }} g</td>
                        <td class="pe-4">{{ number_format((float) $registro->detalles->sum('grasas'), 1) }} g</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-secondary py-5"><i class="fa-solid fa-chart-pie d-block fs-3 mb-2"></i>No hay comidas registradas para esta fecha.</td></tr>
                    @endforelse
                </tbody>
                @if ($registros->isNotEmpty())
                <tfoot><tr class="fw-semibold"><td class="ps-4">Total del día</td><td>{{ $registros->sum(fn ($r) => $r->detalles->count()) }}</td><td>{{ number_format((float) $totales['calorias'], 1) }} kcal</td><td>{{ number_format((float) $totales['proteinas'], 1) }} g</td><td>{{ number_format((float) $totales['carbohidratos'], 1) }} g</td><td class="pe-4">{{ number_format((float) $totales['grasas'], 1) }} g</td></tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2">
    <a href="{{ route('alimentacion.registros.index', ['fecha' => $fecha]) }}" class="btn btn-primary"><i class="fa-solid fa-book-open me-2"></i>Ver registro de comidas</a>
    <a href="{{ route('alimentacion.alimentos.index') }}" class="btn btn-outline-primary"><i class="fa-solid fa-apple-whole me-2"></i>Catálogo de alimentos</a>
</div>
@endsection
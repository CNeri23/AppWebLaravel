@extends('layouts.app')

@section('content')
<div class="card border-0 shadow-sm mb-4"><div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3"><div><h2 class="fw-bold mb-1">Panel nutricional</h2><p class="text-secondary mb-0">Resumen diario de calorías y macronutrientes registrados.</p></div><form method="GET" class="d-flex gap-2"><input type="date" class="form-control" name="fecha" value="{{ $fecha }}"><button class="btn btn-outline-primary">Consultar</button></form></div></div>
<div class="row g-3 mb-4">
@foreach (['calorias' => ['Calorías','kcal','fa-fire-flame-curved'],'proteinas' => ['Proteínas','g','fa-drumstick-bite'],'carbohidratos' => ['Carbohidratos','g','fa-bread-slice'],'grasas' => ['Grasas','g','fa-droplet']] as $key => $info)
<div class="col-6 col-xl-3"><div class="card border-0 shadow-sm h-100"><div class="card-body"><div class="d-flex align-items-center justify-content-between"><span class="text-secondary">{{ $info[0] }}</span><i class="fa-solid {{ $info[2] }} text-primary"></i></div><div class="fs-3 fw-bold mt-2">{{ number_format($totales[$key], 1) }} <small class="fs-6 text-secondary">{{ $info[1] }}</small></div></div></div></div>
@endforeach
</div>
<div class="card border-0 shadow-sm mb-4"><div class="card-header bg-transparent border-0 pt-4 px-4"><h5 class="fw-semibold mb-0">Consumo por comida</h5></div><div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Comida</th><th>Alimentos registrados</th><th>Calorías</th><th>Proteínas</th><th>Carbohidratos</th><th>Grasas</th></tr></thead><tbody>
@forelse($registros as $registro)
<tr><td class="fw-semibold">{{ ucfirst($registro->tipo_comida) }}</td><td>{{ $registro->detalles->count() }}</td><td>{{ number_format($registro->detalles->sum('calorias'),1) }} kcal</td><td>{{ number_format($registro->detalles->sum('proteinas'),1) }} g</td><td>{{ number_format($registro->detalles->sum('carbohidratos'),1) }} g</td><td>{{ number_format($registro->detalles->sum('grasas'),1) }} g</td></tr>
@empty<tr><td colspan="6" class="text-center text-secondary py-4">Todavía no hay comidas registradas para esta fecha.</td></tr>@endforelse
</tbody><tfoot><tr class="fw-bold"><td colspan="2">Total del día</td><td>{{ number_format($totales['calorias'],1) }} kcal</td><td>{{ number_format($totales['proteinas'],1) }} g</td><td>{{ number_format($totales['carbohidratos'],1) }} g</td><td>{{ number_format($totales['grasas'],1) }} g</td></tr></tfoot></table></div></div></div>
<div class="d-flex gap-2"><a href="{{ route('alimentacion.registros.index', ['fecha' => $fecha]) }}" class="btn btn-primary"><i class="fa-solid fa-book-open me-2"></i>Ver registro de comidas</a><a href="{{ route('alimentacion.alimentos.index') }}" class="btn btn-outline-primary"><i class="fa-solid fa-apple-whole me-2"></i>Catálogo de alimentos</a></div>
@endsection

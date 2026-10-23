<?php

namespace App\Http\Controllers;

use App\Models\RegistroComida;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PanelNutricionalController extends Controller
{
    public function index(Request $request): View
    {
        $fecha = $request->date('fecha')?->toDateString() ?? now()->toDateString();
        $registros = RegistroComida::with('detalles')
            ->where('usuario_id', auth()->id())
            ->whereDate('fecha', $fecha)
            ->orderBy('created_at')
            ->get();

        $totales = [
            'calorias' => $registros->sum(fn ($r) => $r->detalles->sum('calorias')),
            'proteinas' => $registros->sum(fn ($r) => $r->detalles->sum('proteinas')),
            'carbohidratos' => $registros->sum(fn ($r) => $r->detalles->sum('carbohidratos')),
            'grasas' => $registros->sum(fn ($r) => $r->detalles->sum('grasas')),
        ];

        return view('alimentacion.panel', compact('registros', 'fecha', 'totales'));
    }
}

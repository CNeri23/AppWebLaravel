<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    public function index()
    {
        $modulos = Modulo::orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('modulos.index', compact('modulos'));
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:modulos,slug'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        Modulo::create($datos);

        return redirect()
            ->route('modulos.index')
            ->with('success', 'Módulo creado correctamente.');
    }

    public function update(Request $request, Modulo $modulo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'unique:modulos,slug,' . $modulo->id,
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $modulo->update($datos);

        return redirect()
            ->route('modulos.index')
            ->with('success', 'Módulo actualizado correctamente.');
    }

    public function toggle(Modulo $modulo)
    {
        $modulo->update([
            'activo' => !$modulo->activo,
        ]);

        return response()->json([
            'success' => true,
            'activo' => $modulo->activo,
            'mensaje' => $modulo->activo
                ? 'Módulo activado correctamente.'
                : 'Módulo desactivado correctamente.',
        ]);
    }

    public function reorder(Request $request, Modulo $modulo)
    {
        $datos = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $vecino = $datos['direccion'] === 'arriba'
            ? Modulo::where('orden', '<', $modulo->orden)
                ->orderByDesc('orden')
                ->orderByDesc('id')
                ->first()
            : Modulo::where('orden', '>', $modulo->orden)
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

        if (!$vecino) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El módulo ya está en ese extremo de la lista.',
            ], 422);
        }

        $ordenModulo = $modulo->orden;
        $ordenVecino = $vecino->orden;

        if ($ordenModulo === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba'
                ? $ordenModulo - 1
                : $ordenModulo + 1;
        }

        $modulo->update(['orden' => $ordenVecino]);
        $vecino->update(['orden' => $ordenModulo]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Orden actualizado correctamente.',
        ]);
    }

    public function destroy(Modulo $modulo)
    {
        $modulo->delete();

        return redirect()
            ->route('modulos.index')
            ->with('success', 'Módulo eliminado correctamente.');
    }
}
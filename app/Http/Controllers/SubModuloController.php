<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Submodulo;
use Illuminate\Http\Request;

class SubModuloController extends Controller
{
    public function index(Modulo $modulo)
    {
        $submodulos = $modulo->submodulos()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('submodulos.index', compact('modulo', 'submodulos'));
    }

    public function store(Request $request, Modulo $modulo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:submodulos,slug'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'ruta' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $modulo->submodulos()->create($datos);

        return redirect()
            ->route('submodulos.index', $modulo)
            ->with('success', 'Submódulo creado correctamente.');
    }

    public function update(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'unique:submodulos,slug,' . $submodulo->id,
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'ruta' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $submodulo->update($datos);

        return redirect()
            ->route('submodulos.index', $submodulo->modulo_id)
            ->with('success', 'Submódulo actualizado correctamente.');
    }

    public function toggle(Submodulo $submodulo)
    {
        $submodulo->update([
            'activo' => !$submodulo->activo,
        ]);

        return response()->json([
            'success' => true,
            'activo' => $submodulo->activo,
            'mensaje' => $submodulo->activo
                ? 'Submódulo activado correctamente.'
                : 'Submódulo desactivado correctamente.',
        ]);
    }

    /**
     * Igual que Modulo::reorder, pero acotado a los submódulos del
     * mismo módulo (no se puede "subir" hacia otro módulo).
     */
    public function reorder(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $vecino = $datos['direccion'] === 'arriba'
            ? Submodulo::where('modulo_id', $submodulo->modulo_id)
                ->where('orden', '<', $submodulo->orden)
                ->orderByDesc('orden')
                ->orderByDesc('id')
                ->first()
            : Submodulo::where('modulo_id', $submodulo->modulo_id)
                ->where('orden', '>', $submodulo->orden)
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

        if (!$vecino) {
            return response()->json([
                'success' => false,
                'mensaje' => 'El submódulo ya está en ese extremo de la lista.',
            ], 422);
        }

        $ordenActual = $submodulo->orden;
        $ordenVecino = $vecino->orden;

        if ($ordenActual === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba'
                ? $ordenActual - 1
                : $ordenActual + 1;
        }

        $submodulo->update(['orden' => $ordenVecino]);
        $vecino->update(['orden' => $ordenActual]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Orden actualizado correctamente.',
        ]);
    }

    public function destroy(Submodulo $submodulo)
    {
        $moduloId = $submodulo->modulo_id;
        $submodulo->delete();

        return redirect()
            ->route('submodulos.index', $moduloId)
            ->with('success', 'Submódulo eliminado correctamente.');
    }
}
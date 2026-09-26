<?php

namespace App\Http\Controllers;

use App\Models\Submodulo;
use App\Models\Accion;
use Illuminate\Http\Request;

class AccionController extends Controller
{
    public function index(Submodulo $submodulo)
    {
        $acciones = $submodulo->acciones()
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return view('acciones.index', compact('submodulo', 'acciones'));
    }

    public function store(Request $request, Submodulo $submodulo)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => ['required', 'string', 'max:100', 'unique:acciones,slug'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $submodulo->acciones()->create($datos);

        return redirect()
            ->route('acciones.index', $submodulo)
            ->with('success', 'Acción creada correctamente.');
    }

    public function update(Request $request, Accion $accion)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'max:100',
                'unique:acciones,slug,' . $accion->id,
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'icono' => ['nullable', 'string', 'max:150'],
            'orden' => ['required', 'integer', 'min:0'],
        ]);

        $accion->update($datos);

        return redirect()
            ->route('acciones.index', $accion->submodulo_id)
            ->with('success', 'Acción actualizada correctamente.');
    }

    public function toggle(Accion $accion)
    {
        $accion->update([
            'activo' => !$accion->activo,
        ]);

        return response()->json([
            'success' => true,
            'activo' => $accion->activo,
            'mensaje' => $accion->activo
                ? 'Acción activada correctamente.'
                : 'Acción desactivada correctamente.',
        ]);
    }

    /**
     * Igual que Modulo::reorder, pero acotado a las acciones del
     * mismo submódulo.
     */
    public function reorder(Request $request, Accion $accion)
    {
        $datos = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $vecino = $datos['direccion'] === 'arriba'
            ? Accion::where('submodulo_id', $accion->submodulo_id)
                ->where('orden', '<', $accion->orden)
                ->orderByDesc('orden')
                ->orderByDesc('id')
                ->first()
            : Accion::where('submodulo_id', $accion->submodulo_id)
                ->where('orden', '>', $accion->orden)
                ->orderBy('orden')
                ->orderBy('id')
                ->first();

        if (!$vecino) {
            return response()->json([
                'success' => false,
                'mensaje' => 'La acción ya está en ese extremo de la lista.',
            ], 422);
        }

        $ordenActual = $accion->orden;
        $ordenVecino = $vecino->orden;

        if ($ordenActual === $ordenVecino) {
            $ordenVecino = $datos['direccion'] === 'arriba'
                ? $ordenActual - 1
                : $ordenActual + 1;
        }

        $accion->update(['orden' => $ordenVecino]);
        $vecino->update(['orden' => $ordenActual]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Orden actualizado correctamente.',
        ]);
    }

    public function destroy(Accion $accion)
    {
        $submoduloId = $accion->submodulo_id;
        $accion->delete();

        return redirect()
            ->route('acciones.index', $submoduloId)
            ->with('success', 'Acción eliminada correctamente.');
    }
}
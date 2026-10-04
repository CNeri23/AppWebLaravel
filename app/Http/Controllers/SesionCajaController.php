<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\MovimientoCaja;
use App\Models\SesionCaja;
use App\Models\Submodulo;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\CurrencyConverter;
use App\Services\PermissionService;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SesionCajaController extends Controller
{
    public function index(
        Request $request,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ) {
        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $settings->get(
            'time_format',
            'H:i'
        );

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $cajaFiltro = $request->filled('caja_id')
            ? Caja::find((int) $request->input('caja_id'))
            : null;

        $sesiones = SesionCaja::with([
            'caja',
            'usuarioApertura',
            'usuarioCierre',
            'usuarioAutorizacion',
        ])
            ->when($cajaFiltro, function ($query) use ($cajaFiltro) {
                $query->where('caja_id', $cajaFiltro->id);
            })
            ->latest('fecha_apertura')
            ->get()
            ->map(function ($sesion) use (
                $zonaHoraria,
                $dateFormat,
                $timeFormat,
                $codigoMoneda,
                $currencyConverter
            ) {
                return $this->formatearSesion(
                    $sesion,
                    $zonaHoraria,
                    $dateFormat,
                    $timeFormat,
                    $codigoMoneda,
                    $currencyConverter
                );
            })
            ->values();

        $cajas = Caja::where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $submoduloCajas = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'cajas')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesCajas = collect();

        if ($submoduloCajas) {
            $accionesCajas = $submoduloCajas->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        // Administradores que pueden autorizar el cierre (distintos del usuario actual)
        $autorizadores = collect();

        if ($accionesCajas->contains('slug', 'cajas.cerrar')) {
            $autorizadores = User::query()
                ->where('id', '!=', auth()->id())
                ->orderBy('name')
                ->get()
                ->filter(function ($usuario) use ($permissionService) {
                    return $permissionService->tieneAccionPorSlug(
                        $usuario,
                        'cajas.autorizar_cierre'
                    );
                })
                ->values();
        }

        return view('cajas.sesiones.index', compact(
            'sesiones',
            'cajas',
            'cajaFiltro',
            'autorizadores',
            'accionesCajas',
            'codigoMoneda',
            'dateFormat',
            'timeFormat',
            'zonaHoraria'
        ));
    }

    public function abrir(
        Request $request,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ) {
        $datos = $request->validate([
            'caja_id' => [
                'required',
                'integer',
                'exists:cajas,id',
            ],
            'fondo_inicial' => [
                'required',
                'numeric',
                'min:0',
            ],
            'observaciones_apertura' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'caja_id.required' => 'Debes seleccionar una caja.',
            'caja_id.exists' => 'La caja seleccionada no existe.',
            'fondo_inicial.required' => 'El fondo inicial es obligatorio.',
            'fondo_inicial.numeric' => 'El fondo inicial debe ser numérico.',
            'fondo_inicial.min' => 'El fondo inicial no puede ser negativo.',
            'observaciones_apertura.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $fondoInicial = $currencyConverter->toBase(
            (float) $datos['fondo_inicial'],
            $codigoMoneda
        );

        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $settings->get(
            'time_format',
            'H:i'
        );

        $sesion = DB::transaction(function () use (
            $datos,
            $fondoInicial
        ) {
            $caja = Caja::query()
                ->whereKey($datos['caja_id'])
                ->where('activo', true)
                ->lockForUpdate()
                ->first();

            if (!$caja) {
                throw ValidationException::withMessages([
                    'caja_id' => 'La caja no existe o está inactiva.',
                ]);
            }

            $sesionAbierta = SesionCaja::query()
                ->where('caja_id', $caja->id)
                ->where('estado', SesionCaja::ESTADO_ABIERTA)
                ->exists();

            if ($sesionAbierta) {
                throw ValidationException::withMessages([
                    'caja_id' => 'Esta caja ya tiene una sesión abierta.',
                ]);
            }

            $sesionUsuario = SesionCaja::query()
                ->where('usuario_apertura_id', auth()->id())
                ->where('estado', SesionCaja::ESTADO_ABIERTA)
                ->exists();

            if ($sesionUsuario) {
                throw ValidationException::withMessages([
                    'caja_id' => 'Ya tienes una sesión de caja abierta.',
                ]);
            }

            return SesionCaja::create([
                'caja_id' => $caja->id,
                'usuario_apertura_id' => auth()->id(),
                'fecha_apertura' => Carbon::now(),
                'fondo_inicial' => $fondoInicial,
                'estado' => SesionCaja::ESTADO_ABIERTA,
                'observaciones_apertura' => $datos['observaciones_apertura'] ?? null,
            ]);
        });

        AuditLogService::log(
            module: 'cajas',
            action: 'ABRIR',
            description: 'Se abrió la caja "' . $sesion->caja->nombre .
                '" con un fondo inicial de ' . number_format($fondoInicial, 2, '.', '') .
                ' ' . CurrencyConverter::BASE . '.',
            entity: $sesion
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Caja abierta correctamente.',
            'sesion' => $this->formatearSesion(
                $sesion->load('caja', 'usuarioApertura'),
                $zonaHoraria,
                $dateFormat,
                $timeFormat,
                $codigoMoneda,
                $currencyConverter
            ),
        ]);
    }

    public function actual()
    {
        $sesion = SesionCaja::with([
            'caja',
            'usuarioApertura',
            'movimientos.usuario',
            'movimientos.pago',
        ])
            ->where('estado', SesionCaja::ESTADO_ABIERTA)
            ->where('usuario_apertura_id', auth()->id())
            ->latest('fecha_apertura')
            ->first();

        if (!$sesion) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No tienes una sesión de caja abierta.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'sesion' => $sesion,
        ]);
    }

    public function show(
        SesionCaja $sesion,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ) {
        $sesion->load([
            'caja',
            'usuarioApertura',
            'usuarioCierre',
            'usuarioAutorizacion',
            'movimientos.usuario',
        ]);

        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $settings->get(
            'time_format',
            'H:i'
        );

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $datos = $this->formatearSesion(
            $sesion,
            $zonaHoraria,
            $dateFormat,
            $timeFormat,
            $codigoMoneda,
            $currencyConverter
        );

        $entradas = (float) $sesion->movimientos
            ->where('tipo', MovimientoCaja::TIPO_ENTRADA)
            ->sum('monto');

        $salidas = (float) $sesion->movimientos
            ->where('tipo', MovimientoCaja::TIPO_SALIDA)
            ->sum('monto');

        $esperadoActual = round(
            (float) $sesion->fondo_inicial + $entradas - $salidas,
            2
        );

        $datos['entradas_mostradas'] = $currencyConverter->fromBase(
            $entradas,
            $codigoMoneda
        );

        $datos['salidas_mostradas'] = $currencyConverter->fromBase(
            $salidas,
            $codigoMoneda
        );

        $datos['esperado_actual_mostrado'] = $currencyConverter->fromBase(
            $esperadoActual,
            $codigoMoneda
        );

        $datos['movimientos'] = $sesion->movimientos
            ->sortByDesc('fecha_movimiento')
            ->values()
            ->map(function ($movimiento) use (
                $zonaHoraria,
                $dateFormat,
                $timeFormat,
                $codigoMoneda,
                $currencyConverter
            ) {
                return [
                    'id' => $movimiento->id,
                    'tipo' => $movimiento->tipo,
                    'concepto' => $movimiento->concepto,
                    'monto_mostrado' => $currencyConverter->fromBase(
                        (float) $movimiento->monto,
                        $codigoMoneda
                    ),
                    'fecha_formateada' => Carbon::parse($movimiento->fecha_movimiento)
                        ->timezone($zonaHoraria)
                        ->format($dateFormat . ' ' . $timeFormat),
                    'usuario' => optional($movimiento->usuario)->name,
                    'referencia' => $movimiento->referencia,
                    'observaciones' => $movimiento->observaciones,
                ];
            });

        return response()->json([
            'success' => true,
            'sesion' => $datos,
        ]);
    }

    public function movimientos(SesionCaja $sesion)
    {
        $sesion->load([
            'caja',
            'movimientos.usuario',
            'movimientos.pago',
        ]);

        return response()->json([
            'success' => true,
            'movimientos' => $sesion->movimientos
                ->sortByDesc('fecha_movimiento')
                ->values(),
        ]);
    }

    public function retirar(
        Request $request,
        SesionCaja $sesion,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ) {
        $datos = $request->validate([
            'monto' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'concepto' => [
                'required',
                'string',
                'in:retiro,gasto,compra,ajuste',
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:100',
            ],
            'observaciones' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser numérico.',
            'monto.gt' => 'El monto debe ser mayor que cero.',
            'concepto.required' => 'Debes seleccionar el concepto del movimiento.',
            'concepto.in' => 'El concepto seleccionado no es válido.',
            'referencia.max' => 'La referencia no puede superar los 100 caracteres.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $monto = $currencyConverter->toBase(
            (float) $datos['monto'],
            $codigoMoneda
        );

        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $movimiento = DB::transaction(function () use (
            $sesion,
            $datos,
            $monto,
            $codigoMoneda,
            $currencyConverter
        ) {
            $sesionBloqueada = SesionCaja::query()
                ->whereKey($sesion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sesionBloqueada->estado !== SesionCaja::ESTADO_ABIERTA) {
                throw ValidationException::withMessages([
                    'sesion' => 'No se pueden registrar movimientos en una caja cerrada.',
                ]);
            }

            // El retiro no puede superar el efectivo que hay en la caja
            $movimientosSesion = MovimientoCaja::query()
                ->where('sesion_caja_id', $sesionBloqueada->id)
                ->lockForUpdate()
                ->get();

            $entradas = (float) $movimientosSesion
                ->where('tipo', MovimientoCaja::TIPO_ENTRADA)
                ->sum('monto');

            $salidas = (float) $movimientosSesion
                ->where('tipo', MovimientoCaja::TIPO_SALIDA)
                ->sum('monto');

            $disponible = round(
                (float) $sesionBloqueada->fondo_inicial + $entradas - $salidas,
                2
            );

            if (round($monto, 2) > $disponible) {
                throw ValidationException::withMessages([
                    'monto' => 'El monto supera el efectivo disponible en caja (' .
                        number_format(
                            $currencyConverter->fromBase($disponible, $codigoMoneda),
                            2,
                            '.',
                            ','
                        ) . ' ' . $codigoMoneda . ').',
                ]);
            }

            return MovimientoCaja::create([
                'sesion_caja_id' => $sesionBloqueada->id,
                'pago_id' => null,
                'usuario_id' => auth()->id(),
                'tipo' => MovimientoCaja::TIPO_SALIDA,
                'concepto' => $datos['concepto'],
                'monto' => $monto,
                'fecha_movimiento' => Carbon::now(),
                'referencia' => $datos['referencia'] ?? null,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);
        });

        AuditLogService::log(
            module: 'cajas',
            action: 'MOVIMIENTO_SALIDA',
            description: 'Se registró una salida de caja por ' .
                number_format($monto, 2, '.', '') . ' ' .
                CurrencyConverter::BASE . ', concepto: "' .
                $movimiento->concepto . '".',
            entity: $movimiento
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Movimiento registrado correctamente.',
            'movimiento' => $movimiento,
            'efectivo_disponible_mostrado' => $currencyConverter->fromBase(
                $this->efectivoDisponible($sesion),
                $codigoMoneda
            ),
        ]);
    }

    public function arqueo(
        SesionCaja $sesion,
        CurrencyConverter $currencyConverter,
        SystemSettings $settings
    ) {
        $sesion->load('movimientos');

        $entradas = (float) $sesion->movimientos
            ->where('tipo', MovimientoCaja::TIPO_ENTRADA)
            ->sum('monto');

        $salidas = (float) $sesion->movimientos
            ->where('tipo', MovimientoCaja::TIPO_SALIDA)
            ->sum('monto');

        $efectivoEsperado = round(
            (float) $sesion->fondo_inicial + $entradas - $salidas,
            2
        );

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        return response()->json([
            'success' => true,
            'arqueo' => [
                'fondo_inicial' => (float) $sesion->fondo_inicial,
                'entradas' => $entradas,
                'salidas' => $salidas,
                'efectivo_esperado' => $efectivoEsperado,
                'moneda' => $codigoMoneda,
                'fondo_inicial_mostrado' => $currencyConverter->fromBase(
                    (float) $sesion->fondo_inicial,
                    $codigoMoneda
                ),
                'entradas_mostradas' => $currencyConverter->fromBase(
                    $entradas,
                    $codigoMoneda
                ),
                'salidas_mostradas' => $currencyConverter->fromBase(
                    $salidas,
                    $codigoMoneda
                ),
                'efectivo_esperado_mostrado' => $currencyConverter->fromBase(
                    $efectivoEsperado,
                    $codigoMoneda
                ),
            ],
        ]);
    }

    public function cerrar(
        Request $request,
        SesionCaja $sesion,
        PermissionService $permissionService,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ) {
        $datos = $request->validate([
            'efectivo_contado' => [
                'required',
                'numeric',
                'min:0',
            ],
            'usuario_autorizacion_id' => [
                'required',
                'integer',
                'exists:users,id',
            ],
            'password_autorizacion' => [
                'required',
                'string',
                'max:255',
            ],
            'observaciones_cierre' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'efectivo_contado.required' => 'Debes ingresar el efectivo contado.',
            'efectivo_contado.numeric' => 'El efectivo contado debe ser numérico.',
            'efectivo_contado.min' => 'El efectivo contado no puede ser negativo.',
            'usuario_autorizacion_id.required' => 'Debes seleccionar al administrador que autoriza el cierre.',
            'usuario_autorizacion_id.exists' => 'El usuario autorizador no existe.',
            'password_autorizacion.required' => 'La contraseña del administrador es obligatoria.',
            'observaciones_cierre.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $autorizador = User::findOrFail(
            $datos['usuario_autorizacion_id']
        );

        if (!$permissionService->tieneAccionPorSlug(
            $autorizador,
            'cajas.autorizar_cierre'
        )) {
            throw ValidationException::withMessages([
                'usuario_autorizacion_id' => 'El usuario seleccionado no tiene permiso para autorizar cierres de caja.',
            ]);
        }

        if (!Hash::check(
            $datos['password_autorizacion'],
            $autorizador->password
        )) {
            throw ValidationException::withMessages([
                'password_autorizacion' => 'La contraseña del administrador es incorrecta.',
            ]);
        }

        if ((int) $autorizador->id === (int) auth()->id()) {
            throw ValidationException::withMessages([
                'usuario_autorizacion_id' => 'El usuario que cierra la caja no puede autorizar su propio cierre.',
            ]);
        }

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $efectivoContado = $currencyConverter->toBase(
            (float) $datos['efectivo_contado'],
            $codigoMoneda
        );

        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $settings->get(
            'time_format',
            'H:i'
        );

        $sesionActualizada = DB::transaction(function () use (
            $sesion,
            $datos,
            $autorizador,
            $efectivoContado
        ) {
            $sesionBloqueada = SesionCaja::query()
                ->whereKey($sesion->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sesionBloqueada->estado !== SesionCaja::ESTADO_ABIERTA) {
                throw ValidationException::withMessages([
                    'sesion' => 'Esta sesión de caja ya está cerrada.',
                ]);
            }

            $movimientos = MovimientoCaja::query()
                ->where('sesion_caja_id', $sesionBloqueada->id)
                ->lockForUpdate()
                ->get();

            $entradas = (float) $movimientos
                ->where('tipo', MovimientoCaja::TIPO_ENTRADA)
                ->sum('monto');

            $salidas = (float) $movimientos
                ->where('tipo', MovimientoCaja::TIPO_SALIDA)
                ->sum('monto');

            $efectivoEsperado = round(
                (float) $sesionBloqueada->fondo_inicial +
                $entradas -
                $salidas,
                2
            );

            $diferencia = round(
                $efectivoContado - $efectivoEsperado,
                2
            );

            $sesionBloqueada->usuario_cierre_id = auth()->id();
            $sesionBloqueada->usuario_autorizacion_id = $autorizador->id;
            $sesionBloqueada->fecha_cierre = Carbon::now();
            $sesionBloqueada->efectivo_esperado = $efectivoEsperado;
            $sesionBloqueada->efectivo_contado = $efectivoContado;
            $sesionBloqueada->diferencia = $diferencia;
            $sesionBloqueada->estado = SesionCaja::ESTADO_CERRADA;
            $sesionBloqueada->observaciones_cierre = $datos['observaciones_cierre'] ?? null;
            $sesionBloqueada->save();

            return $sesionBloqueada;
        });

        AuditLogService::log(
            module: 'cajas',
            action: 'CERRAR',
            description: 'Se cerró la caja "' .
                $sesionActualizada->caja->nombre .
                '". Efectivo esperado: ' .
                number_format((float) $sesionActualizada->efectivo_esperado, 2, '.', '') .
                ' ' . CurrencyConverter::BASE .
                '; contado: ' .
                number_format((float) $sesionActualizada->efectivo_contado, 2, '.', '') .
                ' ' . CurrencyConverter::BASE .
                '; diferencia: ' .
                number_format((float) $sesionActualizada->diferencia, 2, '.', '') .
                ' ' . CurrencyConverter::BASE . '.',
            entity: $sesionActualizada
        );

        return response()->json([
            'success' => true,
            'mensaje' => 'Caja cerrada correctamente.',
            'sesion' => $this->formatearSesion(
                $sesionActualizada->load([
                    'caja',
                    'usuarioApertura',
                    'usuarioCierre',
                    'usuarioAutorizacion',
                ]),
                $zonaHoraria,
                $dateFormat,
                $timeFormat,
                $codigoMoneda,
                $currencyConverter
            ),
        ]);
    }

    /**
     * Estructura plana que usan la vista y sesiones.js para pintar una sesión.
     */
    private function formatearSesion(
        SesionCaja $sesion,
        string $zonaHoraria,
        string $dateFormat,
        string $timeFormat,
        string $codigoMoneda,
        CurrencyConverter $currencyConverter
    ): array {
        $convertir = function ($valor) use ($currencyConverter, $codigoMoneda) {
            return $valor !== null
                ? $currencyConverter->fromBase((float) $valor, $codigoMoneda)
                : null;
        };

        $formatoFechaHora = $dateFormat . ' ' . $timeFormat;

        return [
            'id' => $sesion->id,
            'caja_id' => $sesion->caja_id,
            'caja_nombre' => optional($sesion->caja)->nombre,
            'usuario_apertura_nombre' => optional($sesion->usuarioApertura)->name,
            'usuario_cierre_nombre' => optional($sesion->usuarioCierre)->name,
            'usuario_autorizacion_nombre' => optional($sesion->usuarioAutorizacion)->name,
            'estado' => $sesion->estado,
            'fecha_apertura' => $sesion->fecha_apertura,
            'fecha_apertura_formateada' => $sesion->fecha_apertura
                ->timezone($zonaHoraria)
                ->format($formatoFechaHora),
            'fecha_apertura_orden' => $sesion->fecha_apertura->timestamp,
            'fecha_cierre' => $sesion->fecha_cierre,
            'fecha_cierre_formateada' => $sesion->fecha_cierre
                ? $sesion->fecha_cierre
                    ->timezone($zonaHoraria)
                    ->format($formatoFechaHora)
                : null,
            'fondo_inicial_mostrado' => $convertir($sesion->fondo_inicial),
            'efectivo_esperado_mostrado' => $convertir($sesion->efectivo_esperado),
            'efectivo_contado_mostrado' => $convertir($sesion->efectivo_contado),
            'diferencia_mostrada' => $convertir($sesion->diferencia),
            'efectivo_disponible_mostrado' => $sesion->estado === SesionCaja::ESTADO_ABIERTA
                ? $currencyConverter->fromBase(
                    $this->efectivoDisponible($sesion),
                    $codigoMoneda
                )
                : null,
            'observaciones_apertura' => $sesion->observaciones_apertura,
            'observaciones_cierre' => $sesion->observaciones_cierre,
        ];
    }

    private function efectivoDisponible(SesionCaja $sesion): float
    {
        $entradas = (float) $sesion->movimientos()
            ->where('tipo', MovimientoCaja::TIPO_ENTRADA)
            ->sum('monto');

        $salidas = (float) $sesion->movimientos()
            ->where('tipo', MovimientoCaja::TIPO_SALIDA)
            ->sum('monto');

        return round((float) $sesion->fondo_inicial + $entradas - $salidas, 2);
    }
}
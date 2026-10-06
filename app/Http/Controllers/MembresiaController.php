<?php

namespace App\Http\Controllers;

use App\Models\Membresia;
use App\Models\Persona;
use App\Models\Plan;
use App\Models\Submodulo;
use App\Models\Ticket;
use App\Services\AuditLogService;
use App\Services\CurrencyConverter;
use App\Services\MembresiaService;
use App\Services\PermissionService;
use App\Services\TicketService;
use App\Services\SystemSettings;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembresiaController extends Controller
{
    public function index(
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ): View {
        $zonaHoraria = $settings->get(
            'timezone',
            config('app.timezone')
        );

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $submoduloMembresias = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'membresias')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesMembresias = collect();

        if ($submoduloMembresias) {
            $accionesMembresias = $submoduloMembresias->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $submoduloTickets = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'tickets')
            ->where('activo', true)
            ->first();

        $accionesTickets = collect();

        if ($submoduloTickets) {
            $accionesTickets = $submoduloTickets->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $moneda = [
            'codigo' => $codigoMoneda,
            'simbolo' => match ($codigoMoneda) {
                'USD' => '$',
                'EUR' => '€',
                'MXN' => '$',
                default => $codigoMoneda,
            },
            'tasa' => $currencyConverter->rate(
                $codigoMoneda
            ),
        ];

        $membresias = Membresia::with([
            'persona',
            'plan',
            'pagos',
        ])
            ->orderByDesc('fecha_inicio')
            ->get();

        $pagosIds = $membresias
            ->flatMap(function ($membresia) {
                return $membresia->pagos->pluck('id');
            })
            ->filter()
            ->unique()
            ->values();

        $ticketsPorPago = collect();

        if ($pagosIds->isNotEmpty()) {
            $ticketsPorPago = Ticket::query()
                ->whereIn('pago_id', $pagosIds)
                ->get()
                ->keyBy('pago_id');
        }

        $miembros = Persona::query()
            ->whereHas('tipos', function ($query) {
                $query->where('nombre', 'Miembro');
            })
            ->orderBy('nombre')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->get();

        $planes = Plan::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        $planes->each(function ($plan) use ($currencyConverter, $codigoMoneda) {
            $plan->precio_mostrado =
                $currencyConverter->fromBase(
                    (float) $plan->precio,
                    $codigoMoneda
                );
        });

        $membresias->each(function ($membresia) use (
            $currencyConverter,
            $codigoMoneda,
            $dateFormat,
            $ticketsPorPago
        ) {
            $nombreMiembro = trim(
                $membresia->persona->nombre . ' ' .
                $membresia->persona->apellido_paterno . ' ' .
                ($membresia->persona->apellido_materno ?? '')
            );

            $fechaInicio = Carbon::parse(
                $membresia->fecha_inicio
            );

            $fechaFin = Carbon::parse(
                $membresia->fecha_fin
            );

            $membresia->nombre_miembro =
                $nombreMiembro;

            $membresia->plan_nombre =
                $membresia->plan->nombre;

            $membresia->precio_mostrado =
                $currencyConverter->fromBase(
                    (float) $membresia->precio,
                    $codigoMoneda
                );

            $membresia->fecha_inicio_mostrada =
                $fechaInicio->format(
                    $dateFormat
                );

            $membresia->fecha_fin_mostrada =
                $fechaFin->format(
                    $dateFormat
                );

            $membresia->duracion_dias =
                $fechaInicio->diffInDays(
                    $fechaFin
                ) + 1;

            $membresia->moneda =
                $codigoMoneda;

            $ultimoPago = $membresia->pagos
                ->sortByDesc('fecha_pago')
                ->first();

            $membresia->ticket =
                $ultimoPago
                    ? $ticketsPorPago->get($ultimoPago->id)
                    : null;

            $membresia->ticket_url =
                $membresia->ticket
                    ? route(
                        'tickets.show',
                        $membresia->ticket
                    )
                    : null;

            $membresia->urls = [
                'show' => route(
                    'membresias.show',
                    $membresia
                ),
                'editar' => route(
                    'membresias.update',
                    $membresia
                ),
                'renovar' => route(
                    'membresias.renovar',
                    $membresia
                ),
                'cancelar' => route(
                    'membresias.cancelar',
                    $membresia
                ),
                'ticket' => $membresia->ticket_url,
                'ticket_imprimir' => $membresia->ticket
                    ? route(
                        'tickets.print',
                        $membresia->ticket
                    )
                    : null,
            ];
        });

        return view('membresias.index', compact(
            'membresias',
            'miembros',
            'planes',
            'accionesMembresias',
            'accionesTickets',
            'zonaHoraria',
            'moneda',
            'dateFormat'
        ));
    }

    public function store(
        Request $request,
        MembresiaService $membresiaService,
        TicketService $ticketService,
        CurrencyConverter $currencyConverter,
        SystemSettings $settings
    ) {
        $datos = $request->validate([
            'persona_id' => [
                'required',
                'integer',
                'exists:personas,id',
            ],
            'plan_id' => [
                'required',
                'integer',
                'exists:planes,id',
            ],
            'metodo_pago' => [
                'required',
                'string',
                'in:efectivo,tarjeta,transferencia',
            ],
            'monto_recibido' => [
                'nullable',
                'numeric',
                'min:0',
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
            'persona_id.required' => 'El miembro es obligatorio.',
            'persona_id.integer' => 'El miembro seleccionado no es válido.',
            'persona_id.exists' => 'El miembro seleccionado no existe.',

            'plan_id.required' => 'El plan es obligatorio.',
            'plan_id.integer' => 'El plan seleccionado no es válido.',
            'plan_id.exists' => 'El plan seleccionado no existe.',

            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.string' => 'El método de pago no es válido.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',

            'monto_recibido.numeric' => 'El monto recibido debe ser numérico.',
            'monto_recibido.min' => 'El monto recibido no puede ser negativo.',

            'referencia.string' => 'La referencia no es válida.',
            'referencia.max' => 'La referencia no puede superar los 100 caracteres.',

            'observaciones.string' => 'Las observaciones no son válidas.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $montoRecibido = null;

        if (
            $datos['metodo_pago'] === 'efectivo' &&
            $request->filled('monto_recibido')
        ) {
            $montoRecibido =
                $currencyConverter->toBase(
                    (float) $datos['monto_recibido'],
                    $codigoMoneda
                );
        }

        $resultado = $membresiaService->contratar(
            personaId: (int) $datos['persona_id'],
            planId: (int) $datos['plan_id'],
            metodoPago: $datos['metodo_pago'],
            montoRecibido: $montoRecibido,
            referencia: $datos['referencia'] ?? null,
            observaciones: $datos['observaciones'] ?? null
        );

        $membresia = $resultado['membresia'];
        $pago = $resultado['pago'];

        $ticket = $ticketService->crear(
            pago: $pago,
            observaciones: 'Ticket generado por contratación de membresía.'
        );

        $membresia->load([
            'persona',
            'plan',
            'pagos',
        ]);

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $monto = $currencyConverter->fromBase(
            (float) $pago->monto,
            $codigoMoneda
        );

        $nombrePersona = trim(
            $membresia->persona->nombre . ' ' .
            $membresia->persona->apellido_paterno . ' ' .
            ($membresia->persona->apellido_materno ?? '')
        );

        AuditLogService::log(
            module: 'membresias',
            action: 'CREAR',
            description: 'Se contrató la membresía #' .
            $membresia->id .
            ' para "' . $nombrePersona .
            '" con el plan "' . $membresia->plan->nombre .
            '" por ' . number_format($monto, 2) .
            ' ' . $codigoMoneda .
            '. Se registró el pago inicial mediante "' .
            $pago->metodo_pago . '".',
            entity: $membresia
        );

        $fechaInicio = Carbon::parse(
            $membresia->fecha_inicio
        );

        $fechaFin = Carbon::parse(
            $membresia->fecha_fin
        );

        $membresia->nombre_miembro =
            $nombrePersona;

        $membresia->plan_nombre =
            $membresia->plan->nombre;

        $membresia->precio_mostrado =
            $currencyConverter->fromBase(
                (float) $membresia->precio,
                $codigoMoneda
            );

        $membresia->fecha_inicio_mostrada =
            $fechaInicio->format(
                $dateFormat
            );

        $membresia->fecha_fin_mostrada =
            $fechaFin->format(
                $dateFormat
            );

        $membresia->duracion_dias =
            $fechaInicio->diffInDays(
                $fechaFin
            ) + 1;

        $membresia->moneda =
            $codigoMoneda;

        $membresia->ticket_url =
            route(
                'tickets.show',
                $ticket
            );

        $membresia->urls = [
            'show' => route(
                'membresias.show',
                $membresia
            ),
            'editar' => route(
                'membresias.update',
                $membresia
            ),
            'renovar' => route(
                'membresias.renovar',
                $membresia
            ),
            'cancelar' => route(
                'membresias.cancelar',
                $membresia
            ),
            'ticket' => $membresia->ticket_url,
            'ticket_imprimir' => route(
                'tickets.print',
                $ticket
            ),
        ];

        return response()->json([
            'success' => true,
            'mensaje' => 'Membresía contratada correctamente.',
            'membresia' => $membresia,
            'pago' => $pago,
            'ticket' => $ticket,
            'urls' => $membresia->urls,
        ]);
    }

    public function update(
        Request $request,
        Membresia $membresia,
        MembresiaService $membresiaService,
        CurrencyConverter $currencyConverter,
        SystemSettings $settings
    ) {
        $datos = $request->validate([
            'observaciones' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'observaciones.string' => 'Las observaciones no son válidas.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $membresia = $membresiaService->actualizar(
            membresiaId: (int) $membresia->id,
            observaciones: $datos['observaciones'] ?? null
        );

        $membresia->load([
            'persona',
            'plan',
            'pagos',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $nombrePersona = trim(
            $membresia->persona->nombre . ' ' .
            $membresia->persona->apellido_paterno . ' ' .
            ($membresia->persona->apellido_materno ?? '')
        );

        AuditLogService::log(
            module: 'membresias',
            action: 'EDITAR',
            description: 'Se actualizaron los datos administrativos de la membresía #' .
            $membresia->id .
            ' de "' . $nombrePersona . '".',
            entity: $membresia
        );

        $fechaInicio = Carbon::parse(
            $membresia->fecha_inicio
        );

        $fechaFin = Carbon::parse(
            $membresia->fecha_fin
        );

        $membresia->nombre_miembro =
            $nombrePersona;

        $membresia->plan_nombre =
            $membresia->plan->nombre;

        $membresia->precio_mostrado =
            $currencyConverter->fromBase(
                (float) $membresia->precio,
                $codigoMoneda
            );

        $membresia->fecha_inicio_mostrada =
            $fechaInicio->format(
                $dateFormat
            );

        $membresia->fecha_fin_mostrada =
            $fechaFin->format(
                $dateFormat
            );

        $membresia->duracion_dias =
            $fechaInicio->diffInDays(
                $fechaFin
            ) + 1;

        $membresia->moneda =
            $codigoMoneda;

        return response()->json([
            'success' => true,
            'mensaje' => 'Membresía actualizada correctamente.',
            'membresia' => $membresia,
        ]);
    }

    public function renovar(
        Request $request,
        Membresia $membresia,
        MembresiaService $membresiaService,
        TicketService $ticketService,
        CurrencyConverter $currencyConverter,
        SystemSettings $settings
    ) {
        $datos = $request->validate([
            'plan_id' => [
                'required',
                'integer',
                'exists:planes,id',
            ],
            'metodo_pago' => [
                'required',
                'string',
                'in:efectivo,tarjeta,transferencia',
            ],
            'monto_recibido' => [
                'nullable',
                'numeric',
                'min:0',
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
            'plan_id.required' => 'El plan es obligatorio.',
            'plan_id.integer' => 'El plan seleccionado no es válido.',
            'plan_id.exists' => 'El plan seleccionado no existe.',

            'metodo_pago.required' => 'El método de pago es obligatorio.',
            'metodo_pago.string' => 'El método de pago no es válido.',
            'metodo_pago.in' => 'El método de pago seleccionado no es válido.',

            'monto_recibido.numeric' => 'El monto recibido debe ser numérico.',
            'monto_recibido.min' => 'El monto recibido no puede ser negativo.',

            'referencia.string' => 'La referencia no es válida.',
            'referencia.max' => 'La referencia no puede superar los 100 caracteres.',

            'observaciones.string' => 'Las observaciones no son válidas.',
            'observaciones.max' => 'Las observaciones no pueden superar los 255 caracteres.',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $montoRecibido = null;

        if (
            $datos['metodo_pago'] === 'efectivo' &&
            $request->filled('monto_recibido')
        ) {
            $montoRecibido =
                $currencyConverter->toBase(
                    (float) $datos['monto_recibido'],
                    $codigoMoneda
                );
        }

        $resultado = $membresiaService->renovar(
            membresiaId: (int) $membresia->id,
            planId: (int) $datos['plan_id'],
            metodoPago: $datos['metodo_pago'],
            montoRecibido: $montoRecibido,
            referencia: $datos['referencia'] ?? null,
            observaciones: $datos['observaciones'] ?? null
        );

        $membresia = $resultado['membresia'];
        $pago = $resultado['pago'];

        $ticket = $ticketService->crear(
            pago: $pago,
            observaciones: 'Ticket generado por renovación de membresía.'
        );

        $membresia->load([
            'persona',
            'plan',
            'pagos',
        ]);

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $monto = $currencyConverter->fromBase(
            (float) $pago->monto,
            $codigoMoneda
        );

        $nombrePersona = trim(
            $membresia->persona->nombre . ' ' .
            $membresia->persona->apellido_paterno . ' ' .
            ($membresia->persona->apellido_materno ?? '')
        );

        AuditLogService::log(
            module: 'membresias',
            action: 'RENOVAR',
            description: 'Se renovó la membresía #' .
            $membresia->id .
            ' de "' . $nombrePersona .
            '" con el plan "' . $membresia->plan->nombre .
            '" por ' . number_format($monto, 2) .
            ' ' . $codigoMoneda .
            '. Se registró el pago de renovación mediante "' .
            $pago->metodo_pago . '".',
            entity: $membresia
        );

        $fechaInicio = Carbon::parse(
            $membresia->fecha_inicio
        );

        $fechaFin = Carbon::parse(
            $membresia->fecha_fin
        );

        $membresia->nombre_miembro =
            $nombrePersona;

        $membresia->plan_nombre =
            $membresia->plan->nombre;

        $membresia->precio_mostrado =
            $currencyConverter->fromBase(
                (float) $membresia->precio,
                $codigoMoneda
            );

        $membresia->fecha_inicio_mostrada =
            $fechaInicio->format(
                $dateFormat
            );

        $membresia->fecha_fin_mostrada =
            $fechaFin->format(
                $dateFormat
            );

        $membresia->duracion_dias =
            $fechaInicio->diffInDays(
                $fechaFin
            ) + 1;

        $membresia->moneda =
            $codigoMoneda;

        $membresia->ticket_url =
            route(
                'tickets.show',
                $ticket
            );

        $membresia->urls = [
            'show' => route(
                'membresias.show',
                $membresia
            ),
            'editar' => route(
                'membresias.update',
                $membresia
            ),
            'renovar' => route(
                'membresias.renovar',
                $membresia
            ),
            'cancelar' => route(
                'membresias.cancelar',
                $membresia
            ),
            'ticket' => $membresia->ticket_url,
            'ticket_imprimir' => route(
                'tickets.print',
                $ticket
            ),
        ];

        return response()->json([
            'success' => true,
            'mensaje' => 'Membresía renovada correctamente.',
            'membresia' => $membresia,
            'pago' => $pago,
            'ticket' => $ticket,
            'urls' => $membresia->urls,
        ]);
    }

    public function show(
        Membresia $membresia,
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ): View {
        $membresia->load([
            'persona',
            'plan',
            'pagos',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $timeFormat = $settings->get(
            'time_format',
            'H:i'
        );

        $submoduloMembresias = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'membresias')
            ->where('activo', true)
            ->first();

        $permissionService = app(PermissionService::class);

        $accionesMembresias = collect();

        if ($submoduloMembresias) {
            $accionesMembresias = $submoduloMembresias->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $submoduloTickets = Submodulo::with([
            'acciones' => function ($query) {
                $query
                    ->where('activo', true)
                    ->orderBy('orden')
                    ->orderBy('nombre');
            }
        ])
            ->where('slug', 'tickets')
            ->where('activo', true)
            ->first();

        $accionesTickets = collect();

        if ($submoduloTickets) {
            $accionesTickets = $submoduloTickets->acciones
                ->filter(function ($accion) use ($permissionService) {
                    return $permissionService->tieneAccion(
                        auth()->user(),
                        $accion->id
                    );
                })
                ->values();
        }

        $pagosIds = $membresia->pagos
            ->pluck('id')
            ->filter()
            ->unique()
            ->values();

        $ticketsPorPago = collect();

        if ($pagosIds->isNotEmpty()) {
            $ticketsPorPago = Ticket::query()
                ->whereIn('pago_id', $pagosIds)
                ->get()
                ->keyBy('pago_id');
        }

        $fechaInicio = Carbon::parse(
            $membresia->fecha_inicio
        );

        $fechaFin = Carbon::parse(
            $membresia->fecha_fin
        );

        $membresia->precio_mostrado =
            $currencyConverter->fromBase(
                (float) $membresia->precio,
                $codigoMoneda
            );

        $membresia->fecha_inicio_mostrada =
            $fechaInicio->format(
                $dateFormat
            );

        $membresia->fecha_fin_mostrada =
            $fechaFin->format(
                $dateFormat
            );

        $membresia->duracion_dias =
            $fechaInicio->diffInDays(
                $fechaFin
            ) + 1;

        $membresia->moneda =
            $codigoMoneda;

        $membresia->pagos->each(
            function ($pago) use (
                $currencyConverter,
                $codigoMoneda,
                $dateFormat,
                $timeFormat,
                $ticketsPorPago
            ) {
                $fechaPago = Carbon::parse(
                    $pago->fecha_pago
                );

                $pago->monto_mostrado =
                    $currencyConverter->fromBase(
                        (float) $pago->monto,
                        $codigoMoneda
                    );

                $pago->fecha_pago_mostrada =
                    $fechaPago->format(
                        $dateFormat . ' ' . $timeFormat
                    );

                $pago->moneda =
                    $codigoMoneda;

                $pago->ticket =
                    $ticketsPorPago->get(
                        $pago->id
                    );

                $pago->ticket_url =
                    $pago->ticket
                        ? route(
                            'tickets.show',
                            $pago->ticket
                        )
                        : null;
            }
        );

        $ultimoPago = $membresia->pagos
            ->sortByDesc('fecha_pago')
            ->first();

        $membresia->ticket =
            $ultimoPago?->ticket;

        $membresia->ticket_url =
            $ultimoPago?->ticket_url;

        return view('membresias.show', compact(
            'membresia',
            'codigoMoneda',
            'dateFormat',
            'timeFormat',
            'accionesMembresias',
            'accionesTickets'
        ));
    }

    public function cancelar(
        Membresia $membresia,
        MembresiaService $membresiaService,
        CurrencyConverter $currencyConverter,
        SystemSettings $settings
    ) {
        $membresia = $membresiaService->cancelar(
            (int) $membresia->id
        );

        $membresia->load([
            'persona',
            'plan',
            'pagos',
        ]);

        $codigoMoneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $dateFormat = $settings->get(
            'date_format',
            'd/m/Y'
        );

        $fechaInicio = Carbon::parse(
            $membresia->fecha_inicio
        );

        $fechaFin = Carbon::parse(
            $membresia->fecha_fin
        );

        $monto = $currencyConverter->fromBase(
            (float) $membresia->precio,
            $codigoMoneda
        );

        $nombrePersona = trim(
            $membresia->persona->nombre . ' ' .
            $membresia->persona->apellido_paterno . ' ' .
            ($membresia->persona->apellido_materno ?? '')
        );

        AuditLogService::log(
            module: 'membresias',
            action: 'CANCELAR',
            description: 'Se canceló la membresía #' .
            $membresia->id .
            ' de "' . $nombrePersona .
            '" con el plan "' . $membresia->plan->nombre .
            '" por ' . number_format($monto, 2) .
            ' ' . $codigoMoneda . '.',
            entity: $membresia
        );

        $membresia->nombre_miembro =
            $nombrePersona;

        $membresia->plan_nombre =
            $membresia->plan->nombre;

        $membresia->precio_mostrado =
            $monto;

        $membresia->fecha_inicio_mostrada =
            $fechaInicio->format(
                $dateFormat
            );

        $membresia->fecha_fin_mostrada =
            $fechaFin->format(
                $dateFormat
            );

        $membresia->duracion_dias =
            $fechaInicio->diffInDays(
                $fechaFin
            ) + 1;

        $membresia->moneda =
            $codigoMoneda;

        $membresia->urls = [
            'show' => route(
                'membresias.show',
                $membresia
            ),
            'editar' => route(
                'membresias.update',
                $membresia
            ),
            'renovar' => route(
                'membresias.renovar',
                $membresia
            ),
            'cancelar' => route(
                'membresias.cancelar',
                $membresia
            ),
        ];

        return response()->json([
            'success' => true,
            'mensaje' => 'Membresía cancelada correctamente.',
            'membresia' => $membresia,
            'urls' => $membresia->urls,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use App\Services\CurrencyConverter;
use App\Services\SystemSettings;
use Illuminate\View\View;

class PagoController extends Controller
{
    public function index(
        SystemSettings $settings,
        CurrencyConverter $currencyConverter
    ): View {
        $moneda = $settings->get(
            'currency',
            CurrencyConverter::BASE
        );

        $pagos = Pago::with([
            'membresia.persona',
            'membresia.plan',
        ])
            ->orderByDesc('fecha_pago')
            ->get();

        $pagos->each(function ($pago) use (
            $currencyConverter,
            $moneda
        ) {
            $pago->monto_mostrado = $currencyConverter->fromBase(
                (float) $pago->monto,
                $moneda
            );

            $pago->moneda = $moneda;
        });

        return view('pagos.index', compact(
            'pagos',
            'moneda'
        ));
    }

    public function show(Pago $pago): View
    {
        $pago->load([
            'membresia.persona',
            'membresia.plan',
        ]);

        return view('pagos.show', compact(
            'pago'
        ));
    }
}
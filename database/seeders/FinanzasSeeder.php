<?php

namespace Database\Seeders;

use App\Models\Accion;
use App\Models\Modulo;
use App\Models\Submodulo;
use Illuminate\Database\Seeder;

class FinanzasSeeder extends Seeder
{
    public function run(): void
    {
        $modulo = Modulo::updateOrCreate(
            ['slug' => 'finanzas'],
            [
                'nombre' => 'Finanzas',
                'descripcion' => 'Administración de finanzas personales y del gimnasio.',
                'icono' => '<i class="fa-solid fa-wallet"></i>',
                'orden' => 60,
                'activo' => true,
            ]
        );

        $submodulos = [
            'dashboard' => [
                'nombre' => 'Dashboard financiero',
                'descripcion' => 'Resumen e indicadores financieros.',
                'icono' => '<i class="fa-solid fa-chart-line"></i>',
                'ruta' => 'finanzas.dashboard.index',
                'acciones' => [],
            ],
            'movimientos' => [
                'nombre' => 'Movimientos',
                'descripcion' => 'Registro de ingresos y gastos.',
                'icono' => '<i class="fa-solid fa-arrow-right-arrow-left"></i>',
                'ruta' => 'finanzas.movimientos.index',
                'acciones' => [
                    'finanzas.movimientos.crear' => ['Crear movimiento', 'fa-plus'],
                    'finanzas.movimientos.editar' => ['Editar movimiento', 'fa-pen'],
                    'finanzas.movimientos.eliminar' => ['Eliminar movimiento', 'fa-trash'],
                ],
            ],
            'pagos-recurrentes' => [
                'nombre' => 'Pagos recurrentes',
                'descripcion' => 'Suscripciones, pagos fijos y próximos vencimientos.',
                'icono' => '<i class="fa-solid fa-calendar-clock"></i>',
                'ruta' => 'finanzas.pagos-recurrentes.index',
                'acciones' => [
                    'finanzas.pagos-recurrentes.crear' => ['Crear pago recurrente', 'fa-plus'],
                    'finanzas.pagos-recurrentes.editar' => ['Editar pago recurrente', 'fa-pen'],
                    'finanzas.pagos-recurrentes.eliminar' => ['Eliminar pago recurrente', 'fa-trash'],
                    'finanzas.pagos-recurrentes.confirmar' => ['Confirmar pago', 'fa-check'],
                ],
            ],
            'ahorros' => [
                'nombre' => 'Ahorros',
                'descripcion' => 'Metas y seguimiento de ahorros.',
                'icono' => '<i class="fa-solid fa-piggy-bank"></i>',
                'ruta' => 'finanzas.ahorros.index',
                'acciones' => [
                    'finanzas.ahorros.crear' => ['Crear meta de ahorro', 'fa-plus'],
                    'finanzas.ahorros.editar' => ['Editar meta de ahorro', 'fa-pen'],
                    'finanzas.ahorros.eliminar' => ['Eliminar meta de ahorro', 'fa-trash'],
                ],
            ],
            'reportes' => [
                'nombre' => 'Reportes',
                'descripcion' => 'Reportes y comparativas financieras.',
                'icono' => '<i class="fa-solid fa-chart-pie"></i>',
                'ruta' => 'finanzas.reportes.index',
                'acciones' => [],
            ],
            'categorias' => [
                'nombre' => 'Categorías',
                'descripcion' => 'Administración de categorías financieras.',
                'icono' => '<i class="fa-solid fa-tags"></i>',
                'ruta' => 'finanzas.categorias.index',
                'acciones' => [
                    'finanzas.categorias.crear' => ['Crear categoría', 'fa-plus'],
                    'finanzas.categorias.editar' => ['Editar categoría', 'fa-pen'],
                    'finanzas.categorias.eliminar' => ['Eliminar categoría', 'fa-trash'],
                ],
            ],
        ];

        foreach ($submodulos as $slug => $config) {
            $submodulo = Submodulo::updateOrCreate(
                ['slug' => $slug],
                [
                    'modulo_id' => $modulo->id,
                    'nombre' => $config['nombre'],
                    'descripcion' => $config['descripcion'],
                    'icono' => $config['icono'],
                    'ruta' => $config['ruta'],
                    'orden' => array_search($slug, array_keys($submodulos), true) + 1,
                    'activo' => true,
                ]
            );

            $ordenAccion = 1;

            foreach ($config['acciones'] as $accionSlug => [$nombre, $icono]) {
                Accion::updateOrCreate(
                    ['slug' => $accionSlug],
                    [
                        'submodulo_id' => $submodulo->id,
                        'nombre' => $nombre,
                        'descripcion' => $nombre.' dentro del módulo de finanzas.',
                        'icono' => '<i class="fa-solid '.$icono.'"></i>',
                        'orden' => $ordenAccion++,
                        'activo' => true,
                    ]
                );
            }
        }
    }
}

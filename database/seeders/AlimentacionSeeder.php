<?php

namespace Database\Seeders;

use App\Models\Accion;
use App\Models\Modulo;
use App\Models\Submodulo;
use Illuminate\Database\Seeder;

class AlimentacionSeeder extends Seeder
{
    public function run(): void
    {
        $modulo = Modulo::updateOrCreate(
            ['slug' => 'alimentacion'],
            ['nombre' => 'Alimentación', 'descripcion' => 'Catálogo y seguimiento nutricional.', 'icono' => '<i class="fa-solid fa-apple-whole"></i>', 'orden' => 50, 'activo' => true]
        );

        $submodulos = [
            'alimentos' => [
                'nombre' => 'Catálogo de alimentos',
                'descripcion' => 'Administrar alimentos y valores nutricionales.',
                'icono' => '<i class="fa-solid fa-carrot"></i>',
                'ruta' => 'alimentacion.alimentos.index',
                'acciones' => [
                    'alimentos.crear' => ['Crear alimento', 'fa-plus'],
                    'alimentos.editar' => ['Editar alimento', 'fa-pen'],
                    'alimentos.eliminar' => ['Eliminar alimento', 'fa-trash'],
                ],
            ],
            'registro-comidas' => [
                'nombre' => 'Registro de comidas',
                'descripcion' => 'Registrar comidas y cantidades consumidas.',
                'icono' => '<i class="fa-solid fa-utensils"></i>',
                'ruta' => 'alimentacion.registros.index',
                'acciones' => [
                    'registro-comidas.crear' => ['Registrar comida', 'fa-plus'],
                    'registro-comidas.eliminar' => ['Eliminar registro', 'fa-trash'],
                ],
            ],
            'panel-nutricional' => [
                'nombre' => 'Panel nutricional',
                'descripcion' => 'Resumen diario de calorías y macronutrientes.',
                'icono' => '<i class="fa-solid fa-chart-pie"></i>',
                'ruta' => 'alimentacion.panel.index',
                'acciones' => [],
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

            foreach ($config['acciones'] as $accionSlug => [$nombre, $icono]) {
                Accion::updateOrCreate(
                    ['slug' => $accionSlug],
                    [
                        'submodulo_id' => $submodulo->id,
                        'nombre' => $nombre,
                        'descripcion' => $nombre.' dentro del módulo de alimentación.',
                        'icono' => '<i class="fa-solid '.$icono.'"></i>',
                        'activo' => true,
                    ]
                );
            }
        }
    }
}

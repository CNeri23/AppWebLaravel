<?php

namespace Database\Seeders;

use App\Models\Alimento;
use Illuminate\Database\Seeder;

class AlimentosCatalogoSeeder extends Seeder
{
    /**
     * Catálogo inicial de alimentos.
     *
     * Los valores nutricionales corresponden a 100 g o 100 ml.
     * Es idempotente: si ya existe un alimento con el mismo nombre,
     * actualiza sus valores en lugar de duplicarlo.
     */
    public function run(): void
    {
        $alimentos = [
            // Carnes, pescados y huevos
            ['nombre' => 'Pechuga de pollo cocida', 'categoria' => 'Carnes', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 165, 'proteinas' => 31.00, 'carbohidratos' => 0.00, 'grasas' => 3.60],
            ['nombre' => 'Pechuga de pollo cruda', 'categoria' => 'Carnes', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 120, 'proteinas' => 22.50, 'carbohidratos' => 0.00, 'grasas' => 2.60],
            ['nombre' => 'Carne de res magra cocida', 'categoria' => 'Carnes', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 217, 'proteinas' => 26.10, 'carbohidratos' => 0.00, 'grasas' => 11.80],
            ['nombre' => 'Carne molida de res cocida', 'categoria' => 'Carnes', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 250, 'proteinas' => 26.00, 'carbohidratos' => 0.00, 'grasas' => 15.00],
            ['nombre' => 'Atún en agua escurrido', 'categoria' => 'Pescados', 'unidad_base' => 'g', 'nombre_porcion' => 'Lata escurrida', 'cantidad_porcion' => 130, 'calorias' => 116, 'proteinas' => 25.50, 'carbohidratos' => 0.00, 'grasas' => 0.80],
            ['nombre' => 'Salmón cocido', 'categoria' => 'Pescados', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 206, 'proteinas' => 22.10, 'carbohidratos' => 0.00, 'grasas' => 12.40],
            ['nombre' => 'Huevo entero', 'categoria' => 'Huevos y lácteos', 'unidad_base' => 'g', 'nombre_porcion' => 'Huevo mediano', 'cantidad_porcion' => 50, 'calorias' => 143, 'proteinas' => 12.60, 'carbohidratos' => 0.70, 'grasas' => 9.50],
            ['nombre' => 'Clara de huevo', 'categoria' => 'Huevos y lácteos', 'unidad_base' => 'g', 'nombre_porcion' => 'Clara', 'cantidad_porcion' => 33, 'calorias' => 52, 'proteinas' => 10.90, 'carbohidratos' => 0.70, 'grasas' => 0.20],

            // Lácteos
            ['nombre' => 'Leche entera', 'categoria' => 'Lácteos', 'unidad_base' => 'ml', 'nombre_porcion' => 'Vaso', 'cantidad_porcion' => 250, 'calorias' => 61, 'proteinas' => 3.20, 'carbohidratos' => 4.80, 'grasas' => 3.30],
            ['nombre' => 'Leche descremada', 'categoria' => 'Lácteos', 'unidad_base' => 'ml', 'nombre_porcion' => 'Vaso', 'cantidad_porcion' => 250, 'calorias' => 34, 'proteinas' => 3.40, 'carbohidratos' => 5.00, 'grasas' => 0.10],
            ['nombre' => 'Yogur griego natural', 'categoria' => 'Lácteos', 'unidad_base' => 'g', 'nombre_porcion' => 'Envase', 'cantidad_porcion' => 170, 'calorias' => 97, 'proteinas' => 9.00, 'carbohidratos' => 3.90, 'grasas' => 5.00],
            ['nombre' => 'Queso panela', 'categoria' => 'Lácteos', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 40, 'calorias' => 200, 'proteinas' => 20.00, 'carbohidratos' => 3.00, 'grasas' => 12.00],

            // Cereales y tubérculos
            ['nombre' => 'Avena en hojuelas', 'categoria' => 'Cereales', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 40, 'calorias' => 389, 'proteinas' => 16.90, 'carbohidratos' => 66.30, 'grasas' => 6.90],
            ['nombre' => 'Arroz blanco cocido', 'categoria' => 'Cereales', 'unidad_base' => 'g', 'nombre_porcion' => 'Taza', 'cantidad_porcion' => 158, 'calorias' => 130, 'proteinas' => 2.70, 'carbohidratos' => 28.20, 'grasas' => 0.30],
            ['nombre' => 'Pasta cocida', 'categoria' => 'Cereales', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 140, 'calorias' => 157, 'proteinas' => 5.80, 'carbohidratos' => 30.90, 'grasas' => 0.90],
            ['nombre' => 'Tortilla de maíz', 'categoria' => 'Cereales', 'unidad_base' => 'g', 'nombre_porcion' => 'Tortilla', 'cantidad_porcion' => 30, 'calorias' => 218, 'proteinas' => 5.70, 'carbohidratos' => 44.60, 'grasas' => 2.90],
            ['nombre' => 'Pan integral', 'categoria' => 'Cereales', 'unidad_base' => 'g', 'nombre_porcion' => 'Rebanada', 'cantidad_porcion' => 30, 'calorias' => 247, 'proteinas' => 13.00, 'carbohidratos' => 41.00, 'grasas' => 4.20],
            ['nombre' => 'Papa cocida', 'categoria' => 'Tubérculos', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 150, 'calorias' => 87, 'proteinas' => 1.90, 'carbohidratos' => 20.10, 'grasas' => 0.10],

            // Frutas
            ['nombre' => 'Plátano', 'categoria' => 'Frutas', 'unidad_base' => 'g', 'nombre_porcion' => 'Pieza mediana', 'cantidad_porcion' => 118, 'calorias' => 89, 'proteinas' => 1.10, 'carbohidratos' => 22.80, 'grasas' => 0.30],
            ['nombre' => 'Manzana', 'categoria' => 'Frutas', 'unidad_base' => 'g', 'nombre_porcion' => 'Pieza mediana', 'cantidad_porcion' => 182, 'calorias' => 52, 'proteinas' => 0.30, 'carbohidratos' => 13.80, 'grasas' => 0.20],
            ['nombre' => 'Naranja', 'categoria' => 'Frutas', 'unidad_base' => 'g', 'nombre_porcion' => 'Pieza mediana', 'cantidad_porcion' => 130, 'calorias' => 47, 'proteinas' => 0.90, 'carbohidratos' => 11.80, 'grasas' => 0.10],
            ['nombre' => 'Fresas', 'categoria' => 'Frutas', 'unidad_base' => 'g', 'nombre_porcion' => 'Taza', 'cantidad_porcion' => 150, 'calorias' => 32, 'proteinas' => 0.70, 'carbohidratos' => 7.70, 'grasas' => 0.30],
            ['nombre' => 'Aguacate', 'categoria' => 'Frutas', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 50, 'calorias' => 160, 'proteinas' => 2.00, 'carbohidratos' => 8.50, 'grasas' => 14.70],

            // Verduras
            ['nombre' => 'Brócoli cocido', 'categoria' => 'Verduras', 'unidad_base' => 'g', 'nombre_porcion' => 'Taza', 'cantidad_porcion' => 156, 'calorias' => 35, 'proteinas' => 2.40, 'carbohidratos' => 7.20, 'grasas' => 0.40],
            ['nombre' => 'Espinaca cruda', 'categoria' => 'Verduras', 'unidad_base' => 'g', 'nombre_porcion' => 'Taza', 'cantidad_porcion' => 30, 'calorias' => 23, 'proteinas' => 2.90, 'carbohidratos' => 3.60, 'grasas' => 0.40],
            ['nombre' => 'Jitomate', 'categoria' => 'Verduras', 'unidad_base' => 'g', 'nombre_porcion' => 'Pieza mediana', 'cantidad_porcion' => 123, 'calorias' => 18, 'proteinas' => 0.90, 'carbohidratos' => 3.90, 'grasas' => 0.20],
            ['nombre' => 'Zanahoria', 'categoria' => 'Verduras', 'unidad_base' => 'g', 'nombre_porcion' => 'Pieza mediana', 'cantidad_porcion' => 61, 'calorias' => 41, 'proteinas' => 0.90, 'carbohidratos' => 9.60, 'grasas' => 0.20],

            // Legumbres, semillas y grasas
            ['nombre' => 'Frijoles cocidos', 'categoria' => 'Legumbres', 'unidad_base' => 'g', 'nombre_porcion' => 'Media taza', 'cantidad_porcion' => 86, 'calorias' => 127, 'proteinas' => 8.70, 'carbohidratos' => 22.80, 'grasas' => 0.50],
            ['nombre' => 'Lentejas cocidas', 'categoria' => 'Legumbres', 'unidad_base' => 'g', 'nombre_porcion' => 'Media taza', 'cantidad_porcion' => 99, 'calorias' => 116, 'proteinas' => 9.00, 'carbohidratos' => 20.10, 'grasas' => 0.40],
            ['nombre' => 'Almendras', 'categoria' => 'Semillas y frutos secos', 'unidad_base' => 'g', 'nombre_porcion' => 'Porción', 'cantidad_porcion' => 30, 'calorias' => 579, 'proteinas' => 21.20, 'carbohidratos' => 21.60, 'grasas' => 49.90],
            ['nombre' => 'Crema de cacahuate', 'categoria' => 'Semillas y frutos secos', 'unidad_base' => 'g', 'nombre_porcion' => 'Cucharada', 'cantidad_porcion' => 16, 'calorias' => 588, 'proteinas' => 25.00, 'carbohidratos' => 20.00, 'grasas' => 50.00],
            ['nombre' => 'Aceite de oliva', 'categoria' => 'Grasas y aceites', 'unidad_base' => 'ml', 'nombre_porcion' => 'Cucharada', 'cantidad_porcion' => 15, 'calorias' => 884, 'proteinas' => 0.00, 'carbohidratos' => 0.00, 'grasas' => 100.00],
        ];

        foreach ($alimentos as $alimento) {
            Alimento::updateOrCreate(
                ['nombre' => $alimento['nombre']],
                $alimento + ['activo' => true]
            );
        }

        $this->command?->info('Catálogo de alimentos cargado: '.count($alimentos).' alimentos.');
    }
}

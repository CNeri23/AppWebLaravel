<?php

namespace Database\Seeders;

use App\Models\Tipo;
use Illuminate\Database\Seeder;

class TipoSeeder extends Seeder
{
    public function run(): void
    {
        Tipo::updateOrCreate(
            ['nombre' => 'Miembro'],
            [
                'descripcion' => 'Persona registrada como miembro del gimnasio.',
            ]
        );

        Tipo::updateOrCreate(
            ['nombre' => 'Entrenador'],
            [
                'descripcion' => 'Persona que desempeña funciones de entrenador.',
            ]
        );

        Tipo::updateOrCreate(
            ['nombre' => 'Recepcionista'],
            [
                'descripcion' => 'Persona que desempeña funciones de recepción.',
            ]
        );
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alimentos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->string('categoria', 80)->nullable();
            $table->enum('unidad_base', ['g', 'ml'])->default('g');
            $table->string('nombre_porcion', 80)->nullable();
            $table->decimal('cantidad_porcion', 10, 2)->nullable();
            // Valores nutricionales por cada 100 g o 100 ml de la unidad base.
            $table->decimal('calorias', 10, 2)->default(0);
            $table->decimal('proteinas', 10, 2)->default(0);
            $table->decimal('carbohidratos', 10, 2)->default(0);
            $table->decimal('grasas', 10, 2)->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentos');
    }
};

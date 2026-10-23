<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('registros_comida', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('persona_id')->nullable()->constrained('personas')->nullOnDelete();
            $table->date('fecha')->index();
            $table->enum('tipo_comida', ['desayuno', 'comida', 'cena', 'colacion']);
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->index(['usuario_id', 'fecha']);
        });

        Schema::create('registro_comida_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registro_comida_id')->constrained('registros_comida')->cascadeOnDelete();
            $table->foreignId('alimento_id')->nullable()->constrained('alimentos')->nullOnDelete();
            $table->string('nombre_alimento', 150);
            $table->decimal('cantidad', 10, 2);
            $table->enum('unidad', ['g', 'ml', 'porcion']);
            // Copia de los nutrientes calculados para conservar el historial aunque cambie el catálogo.
            $table->decimal('calorias', 10, 2)->default(0);
            $table->decimal('proteinas', 10, 2)->default(0);
            $table->decimal('carbohidratos', 10, 2)->default(0);
            $table->decimal('grasas', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registro_comida_detalles');
        Schema::dropIfExists('registros_comida');
    }
};

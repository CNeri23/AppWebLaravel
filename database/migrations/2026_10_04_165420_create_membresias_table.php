<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnDelete();
            $table->foreignId('plan_id')
                ->constrained('planes')
                ->restrictOnDelete();
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('estado', 20)->default('activa');
            $table->decimal('precio', 10, 2);
            $table->string('observaciones', 255)->nullable();
            $table->timestamps();

            $table->index(['persona_id', 'estado', 'fecha_fin']);
            $table->index(['fecha_inicio', 'fecha_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membresias');
    }
};
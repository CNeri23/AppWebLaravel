<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('persona_id')
                ->constrained('personas')
                ->restrictOnDelete();

            $table->foreignId('membresia_id')
                ->nullable()
                ->constrained('membresias')
                ->nullOnDelete();

            $table->foreignId('usuario_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Día local (según la zona horaria del sistema) para consultas por fecha
            $table->date('fecha');

            $table->dateTime('fecha_hora');

            // false = intento de acceso denegado (membresía vencida, cancelada, etc.)
            $table->boolean('permitido')->default(true);

            $table->string('motivo', 255)->nullable();

            $table->timestamps();

            $table->index(['persona_id', 'fecha']);
            $table->index(['fecha', 'permitido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
    }
};
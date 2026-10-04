<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sesiones_caja', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caja_id')
                ->constrained('cajas')
                ->restrictOnDelete();

            $table->foreignId('usuario_apertura_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('usuario_cierre_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('usuario_autorizacion_id')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->dateTime('fecha_apertura');
            $table->dateTime('fecha_cierre')->nullable();

            $table->decimal('fondo_inicial', 10, 2);

            $table->decimal('efectivo_esperado', 10, 2)->nullable();
            $table->decimal('efectivo_contado', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->string('estado', 20)->default('abierta');

            $table->string('observaciones_apertura', 255)->nullable();
            $table->string('observaciones_cierre', 255)->nullable();

            $table->timestamps();

            $table->index(['caja_id', 'estado']);
            $table->index(['usuario_apertura_id', 'estado']);
            $table->index('fecha_apertura');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sesiones_caja');
    }
};
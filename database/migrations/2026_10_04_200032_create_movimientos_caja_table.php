<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_caja', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sesion_caja_id')
                ->constrained('sesiones_caja')
                ->restrictOnDelete();

            $table->foreignId('pago_id')
                ->nullable()
                ->constrained('pagos')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('tipo', 20);
            $table->string('concepto', 50);
            $table->decimal('monto', 10, 2);

            $table->dateTime('fecha_movimiento');

            $table->string('referencia', 100)->nullable();
            $table->string('observaciones', 255)->nullable();

            $table->timestamps();

            $table->index(['sesion_caja_id', 'tipo']);
            $table->index(['sesion_caja_id', 'fecha_movimiento']);
            $table->index('pago_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_caja');
    }
};
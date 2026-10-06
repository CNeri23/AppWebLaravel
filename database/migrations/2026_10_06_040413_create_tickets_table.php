<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            $table->string('folio', 50)->unique();

            $table->foreignId('pago_id')
                ->constrained('pagos')
                ->restrictOnDelete();

            $table->foreignId('usuario_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('sesion_caja_id')
                ->nullable()
                ->constrained('sesiones_caja')
                ->restrictOnDelete();

            $table->dateTime('fecha_emision');

            $table->text('observaciones')
                ->nullable();

            $table->timestamps();

            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
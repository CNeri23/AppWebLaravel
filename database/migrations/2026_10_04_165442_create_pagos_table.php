<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membresia_id')
                ->constrained('membresias')
                ->restrictOnDelete();
            $table->decimal('monto', 10, 2);
            $table->string('metodo_pago', 30);
            $table->decimal('monto_recibido', 10, 2)->nullable();
            $table->decimal('cambio', 10, 2)->nullable();
            $table->string('referencia', 100)->nullable();
            $table->dateTime('fecha_pago');
            $table->string('observaciones', 255)->nullable();
            $table->timestamps();

            $table->index(['membresia_id', 'fecha_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};

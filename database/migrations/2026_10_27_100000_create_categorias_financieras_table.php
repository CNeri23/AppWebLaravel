<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_financieras', function (Blueprint $table) {
            $table->id();
            $table->enum('ambito', ['personal', 'gimnasio']);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->enum('tipo', ['ingreso', 'gasto']);
            $table->string('descripcion', 255)->nullable();
            $table->timestamps();

            $table->index(['ambito', 'user_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_financieras');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('acciones', function (Blueprint $table) {

            $table->id();

            $table->foreignId('submodulo_id')
                ->constrained('submodulos')
                ->cascadeOnDelete();

            $table->string('nombre', 100);

            $table->string('slug', 100)
                ->unique();

            $table->string('descripcion', 255)
                ->nullable();

            $table->string('icono', 150)
                ->nullable();

            $table->unsignedInteger('orden')
                ->default(0);

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acciones');
    }
};
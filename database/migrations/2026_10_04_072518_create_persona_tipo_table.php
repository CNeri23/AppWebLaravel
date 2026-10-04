<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('persona_tipo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')
                ->constrained('personas')
                ->cascadeOnDelete();
            $table->foreignId('tipo_id')
                ->constrained('tipos')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['persona_id', 'tipo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_tipo');
    }
};
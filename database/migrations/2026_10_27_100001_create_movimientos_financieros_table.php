<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_financieros', function (Blueprint $table) {
            $table->id();
            $table->enum('ambito', ['personal', 'gimnasio']);
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('categoria_financiera_id')
                ->constrained('categorias_financieras')
                ->restrictOnDelete();
            $table->string('concepto', 150);
            $table->decimal('importe', 14, 2);
            $table->date('fecha');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['ambito', 'user_id', 'fecha']);
            $table->index(['categoria_financiera_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_financieros');
    }
};

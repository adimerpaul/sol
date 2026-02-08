<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resultado_mesa_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resultado_mesa_id')->constrained('resultados_mesa')->cascadeOnDelete();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();

            $table->integer('votos')->default(0);
            $table->integer('votos_gobernador')->default(0);
            $table->integer('votos_asambleista_distrito')->default(0);
            $table->integer('votos_asambleista_poblacion')->default(0);
            $table->integer('votos_concejal')->default(0);
            $table->integer('votos_alcalde')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['resultado_mesa_id', 'partido_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_mesa_detalles');
    }
};

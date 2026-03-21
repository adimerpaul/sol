<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesa_ai_control_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_ai_control_id')->constrained('mesa_ai_controles');
            $table->foreignId('partido_id')->constrained('partidos');
            $table->unsignedInteger('votos_gobernador')->default(0);
            $table->unsignedInteger('votos_asambleista_distrito')->default(0);
            $table->unsignedInteger('votos_asambleista_poblacion')->default(0);
            $table->unsignedInteger('votos_concejal')->default(0);
            $table->unsignedInteger('votos_alcalde')->default(0);
            $table->decimal('confianza', 5, 2)->nullable();
            $table->json('fuente_json')->nullable();
            $table->timestamps();

            $table->unique(['mesa_ai_control_id', 'partido_id'], 'mesa_ai_ctrl_det_partido_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_ai_control_detalles');
    }
};

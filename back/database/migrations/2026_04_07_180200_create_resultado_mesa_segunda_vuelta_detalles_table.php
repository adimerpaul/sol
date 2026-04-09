<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultado_mesa_segunda_vuelta_detalles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('resultado_mesa_segunda_vuelta_id');
            $table->unsignedBigInteger('partido_id');
            $table->integer('votos_gobernador')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['resultado_mesa_segunda_vuelta_id', 'partido_id'], 'resultado_sv_detalles_unique');
            $table->foreign('resultado_mesa_segunda_vuelta_id', 'fk_res_sv_detalle_resultado')
                ->references('id')
                ->on('resultados_mesa_segunda_vuelta')
                ->cascadeOnDelete();
            $table->foreign('partido_id', 'fk_res_sv_detalle_partido')
                ->references('id')
                ->on('partidos')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultado_mesa_segunda_vuelta_detalles');
    }
};

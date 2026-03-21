<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesa_ai_controles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas');
            $table->foreignId('resultado_mesa_id')->nullable()->constrained('resultados_mesa');
            $table->foreignId('registrado_por')->nullable()->constrained('users');
            $table->string('fuente_tipo', 30);
            $table->string('fuente_slot', 20)->nullable();
            $table->string('imagen_path');
            $table->string('modelo')->nullable();
            $table->string('estado', 30)->default('procesado');
            $table->unsignedInteger('total_detectado')->default(0);
            $table->unsignedInteger('blancos_gobernador')->default(0);
            $table->unsignedInteger('nulos_gobernador')->default(0);
            $table->unsignedInteger('papeletas_no_utilizadas_gobernador')->default(0);
            $table->unsignedInteger('blancos_asambleista_distrito')->default(0);
            $table->unsignedInteger('nulos_asambleista_distrito')->default(0);
            $table->unsignedInteger('papeletas_no_utilizadas_asambleista_distrito')->default(0);
            $table->unsignedInteger('blancos_asambleista_poblacion')->default(0);
            $table->unsignedInteger('nulos_asambleista_poblacion')->default(0);
            $table->unsignedInteger('papeletas_no_utilizadas_asambleista_poblacion')->default(0);
            $table->unsignedInteger('blancos_concejal')->default(0);
            $table->unsignedInteger('nulos_concejal')->default(0);
            $table->unsignedInteger('papeletas_no_utilizadas_concejal')->default(0);
            $table->unsignedInteger('blancos_alcalde')->default(0);
            $table->unsignedInteger('nulos_alcalde')->default(0);
            $table->unsignedInteger('papeletas_no_utilizadas_alcalde')->default(0);
            $table->json('resumen_json')->nullable();
            $table->json('respuesta_json')->nullable();
            $table->longText('respuesta_raw')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamp('confirmado_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mesa_ai_controles');
    }
};

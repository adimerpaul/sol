<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('municipio_partido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->foreignId('partido_id')->constrained('partidos')->cascadeOnDelete();
            $table->boolean('habilitado_gobernador')->default(true);
            $table->boolean('habilitado_asambleista_poblacion')->default(true);
            $table->boolean('habilitado_asambleista_distrito')->default(true);
            $table->boolean('habilitado_alcalde')->default(true);
            $table->boolean('habilitado_concejal')->default(true);
            $table->timestamps();

            $table->unique(['municipio_id', 'partido_id'], 'municipio_partido_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('municipio_partido');
    }
};

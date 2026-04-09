<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resultados_mesa_segunda_vuelta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->unique('mesa_id');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('origen_registro', 30)->default('sistema');
            $table->integer('total_votos')->default(0);
            $table->integer('total_validos')->default(0);
            $table->integer('total_blancos')->default(0);
            $table->integer('total_nulos')->default(0);
            $table->integer('blancos')->default(0);
            $table->integer('nulos')->default(0);
            $table->integer('papeletas_no_utilizadas')->default(0);
            $table->string('foto_pizarra')->nullable();
            $table->string('foto_acta')->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_mesa_segunda_vuelta');
    }
};

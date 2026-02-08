<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('resultados_mesa', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mesa_id')->constrained('mesas')->cascadeOnDelete();
            $table->unique('mesa_id'); // 1 resultado por mesa

            // quién registró/actualizó (opcional)
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();

            // ✅ avisos / etapas (booleanos)
            $table->boolean('aviso_antes')->default(false);
            $table->boolean('aviso_manana')->default(false);
            $table->boolean('aviso_mediodia')->default(false);
            $table->boolean('aviso_tarde')->default(false);

            $table->boolean('etapa_1')->default(false);
            $table->boolean('etapa_2')->default(false);

            // totales
            $table->integer('total_votos')->default(0);
            $table->integer('total_validos')->default(0);
            $table->integer('total_blancos')->default(0);
            $table->integer('total_nulos')->default(0);
//            'foto1','foto2','foto3','foto4',
            $table->string('foto1')->nullable();
            $table->string('foto2')->nullable();
            $table->string('foto3')->nullable();
            $table->string('foto4')->nullable();
            $table->string('foto5')->nullable();
            $table->string('foto6')->nullable();
            $table->string('foto7')->nullable();
            $table->string('foto8')->nullable();
            $table->string('foto9')->nullable();
            $table->string('foto10')->nullable();

            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();

            $table->text('observacion')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resultados_mesa');
    }
};

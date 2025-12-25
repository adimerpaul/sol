<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('resultados_mesas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('pais_id')->constrained('paises');
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('provincia_id')->constrained('provincias');
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->foreignId('localidad_id')->constrained('localidades');
            $table->foreignId('recinto_id')->constrained('recintos');
            $table->foreignId('mesa_id')->constrained('mesas');

            $table->json('resultados'); // { partido_id: votos }
            $table->integer('total_votos')->default(0);

            $table->string('foto_1')->nullable();
            $table->string('foto_2')->nullable();

            $table->boolean('verificado')->default(false);
            $table->string('observacion')->nullable();
            $table->string('estado', 20)->default('REALIZADO');


            $table->timestamp('fecha_hora')->useCurrent();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('mesa_id'); // UNA SOLA VEZ
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultado_mesas');
    }
};

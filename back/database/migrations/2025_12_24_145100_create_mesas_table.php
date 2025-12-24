<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->integer('id_original');

            $table->foreignId('recinto_id')->constrained('recintos');
            $table->foreignId('localidad_id')->constrained('localidades');
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->foreignId('provincia_id')->constrained('provincias');
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->foreignId('pais_id')->constrained('paises');

            $table->integer('numero_mesa');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('mesas');
    }
};

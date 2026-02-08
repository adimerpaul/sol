<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            $table->integer('blancos_gobernador')->default(0);
            $table->integer('nulos_gobernador')->default(0);

            $table->integer('blancos_asambleista_distrito')->default(0);
            $table->integer('nulos_asambleista_distrito')->default(0);

            $table->integer('blancos_asambleista_poblacion')->default(0);
            $table->integer('nulos_asambleista_poblacion')->default(0);

            $table->integer('blancos_concejal')->default(0);
            $table->integer('nulos_concejal')->default(0);

            $table->integer('blancos_alcalde')->default(0);
            $table->integer('nulos_alcalde')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            $table->dropColumn([
                'blancos_gobernador','nulos_gobernador',
                'blancos_asambleista_distrito','nulos_asambleista_distrito',
                'blancos_asambleista_poblacion','nulos_asambleista_poblacion',
                'blancos_concejal','nulos_concejal',
                'blancos_alcalde','nulos_alcalde',
            ]);
        });
    }
};

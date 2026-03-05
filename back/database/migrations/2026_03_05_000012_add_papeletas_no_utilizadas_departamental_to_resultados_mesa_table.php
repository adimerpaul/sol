<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (!Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_gobernador')) {
                $table->integer('papeletas_no_utilizadas_gobernador')->default(0);
            }
            if (!Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_asambleista_distrito')) {
                $table->integer('papeletas_no_utilizadas_asambleista_distrito')->default(0);
            }
            if (!Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_asambleista_poblacion')) {
                $table->integer('papeletas_no_utilizadas_asambleista_poblacion')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_gobernador')) {
                $table->dropColumn('papeletas_no_utilizadas_gobernador');
            }
            if (Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_asambleista_distrito')) {
                $table->dropColumn('papeletas_no_utilizadas_asambleista_distrito');
            }
            if (Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_asambleista_poblacion')) {
                $table->dropColumn('papeletas_no_utilizadas_asambleista_poblacion');
            }
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (!Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_concejal')) {
                $table->integer('papeletas_no_utilizadas_concejal')->default(0);
            }
            if (!Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_alcalde')) {
                $table->integer('papeletas_no_utilizadas_alcalde')->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_concejal')) {
                $table->dropColumn('papeletas_no_utilizadas_concejal');
            }
            if (Schema::hasColumn('resultados_mesa', 'papeletas_no_utilizadas_alcalde')) {
                $table->dropColumn('papeletas_no_utilizadas_alcalde');
            }
        });
    }
};

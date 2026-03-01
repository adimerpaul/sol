<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            $table->text('observacion_gobernador')->nullable()->after('observacion');
            $table->text('observacion_asambleista_distrito')->nullable()->after('observacion_gobernador');
            $table->text('observacion_asambleista_poblacion')->nullable()->after('observacion_asambleista_distrito');
            $table->text('observacion_concejal')->nullable()->after('observacion_asambleista_poblacion');
            $table->text('observacion_alcalde')->nullable()->after('observacion_concejal');
        });
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            $table->dropColumn([
                'observacion_gobernador',
                'observacion_asambleista_distrito',
                'observacion_asambleista_poblacion',
                'observacion_concejal',
                'observacion_alcalde',
            ]);
        });
    }
};


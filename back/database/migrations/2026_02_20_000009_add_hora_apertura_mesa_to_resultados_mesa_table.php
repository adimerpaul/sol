<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (!Schema::hasColumn('resultados_mesa', 'hora_apertura_mesa')) {
                $table->string('hora_apertura_mesa', 5)->nullable()->after('aviso_mediodia');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE resultados_mesa MODIFY aviso_tarde TINYINT(1) NULL");
            DB::statement("ALTER TABLE resultados_mesa MODIFY etapa_1 TINYINT(1) NULL");
            DB::statement("ALTER TABLE resultados_mesa MODIFY etapa_2 TINYINT(1) NULL");
            DB::statement("UPDATE resultados_mesa SET aviso_tarde = NULL, etapa_1 = NULL, etapa_2 = NULL");
        }
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (Schema::hasColumn('resultados_mesa', 'hora_apertura_mesa')) {
                $table->dropColumn('hora_apertura_mesa');
            }
        });
    }
};

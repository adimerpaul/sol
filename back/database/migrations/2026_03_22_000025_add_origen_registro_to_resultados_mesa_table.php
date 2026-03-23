<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (!Schema::hasColumn('resultados_mesa', 'origen_registro')) {
                $table->string('origen_registro', 20)->nullable()->after('registrado_por');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resultados_mesa', function (Blueprint $table) {
            if (Schema::hasColumn('resultados_mesa', 'origen_registro')) {
                $table->dropColumn('origen_registro');
            }
        });
    }
};

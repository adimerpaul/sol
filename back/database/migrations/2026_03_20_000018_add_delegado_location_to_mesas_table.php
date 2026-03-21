<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->decimal('delegado_latitud', 10, 7)
                ->nullable()
                ->after('asistencia_capacitacion');
            $table->decimal('delegado_longitud', 10, 7)
                ->nullable()
                ->after('delegado_latitud');
        });
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropColumn(['delegado_latitud', 'delegado_longitud']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesa_ai_controles', function (Blueprint $table) {
            $table->string('fuente_slot_secundaria', 20)->nullable()->after('fuente_slot');
            $table->string('imagen_path_secundaria')->nullable()->after('imagen_path');
        });
    }

    public function down(): void
    {
        Schema::table('mesa_ai_controles', function (Blueprint $table) {
            $table->dropColumn(['fuente_slot_secundaria', 'imagen_path_secundaria']);
        });
    }
};

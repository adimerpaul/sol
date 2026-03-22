<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesa_ai_controles', function (Blueprint $table) {
            $table->foreignId('confirmado_por')
                ->nullable()
                ->after('registrado_por')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('mesa_ai_controles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmado_por');
        });
    }
};

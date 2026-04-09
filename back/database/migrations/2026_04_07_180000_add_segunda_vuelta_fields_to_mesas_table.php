<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->foreignId('delegado_segunda_vuelta_id')
                ->nullable()
                ->after('delegado_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('estado_segunda_vuelta', 30)
                ->default('PENDIENTE')
                ->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('mesas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('delegado_segunda_vuelta_id');
            $table->dropColumn('estado_segunda_vuelta');
        });
    }
};

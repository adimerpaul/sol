<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('asistencia')->default(false);
            $table->timestamp('asistencia_at')->nullable();
            $table->unsignedBigInteger('asistencia_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'asistencia',
                'asistencia_at',
                'asistencia_by',
            ]);
        });
    }
};

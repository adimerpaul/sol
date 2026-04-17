<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('credencial_entregada')->default(false);
            $table->timestamp('credencial_entregada_at')->nullable();
            $table->unsignedBigInteger('credencial_entregada_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'credencial_entregada',
                'credencial_entregada_at',
                'credencial_entregada_by',
            ]);
        });
    }
};

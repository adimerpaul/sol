<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recinto_jefe', function (Blueprint $table) {
            $table->unique(['recinto_id', 'jefe_id']);
            $table->dropUnique('recinto_jefe_recinto_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('recinto_jefe', function (Blueprint $table) {
            $table->unique(['recinto_id']);
            $table->dropUnique('recinto_jefe_recinto_id_jefe_id_unique');
        });
    }
};

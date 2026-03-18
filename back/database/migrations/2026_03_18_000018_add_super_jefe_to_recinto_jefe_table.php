<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('recinto_jefe', function (Blueprint $table) {
            $table->boolean('super_jefe')
                ->default(false)
                ->after('jefe_id');
        });
    }

    public function down(): void
    {
        Schema::table('recinto_jefe', function (Blueprint $table) {
            $table->dropColumn('super_jefe');
        });
    }
};

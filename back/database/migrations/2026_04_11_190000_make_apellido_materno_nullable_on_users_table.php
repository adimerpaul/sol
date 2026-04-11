<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_materno', 120)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('users')
            ->whereNull('apellido_materno')
            ->update(['apellido_materno' => '']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido_materno', 120)->nullable(false)->change();
        });
    }
};

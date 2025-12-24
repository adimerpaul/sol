<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recinto_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recinto_id')->constrained('recintos')->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
//            $table->unique(['user_id','recinto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recinto_user');
    }
};

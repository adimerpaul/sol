<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('jefe_delegado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('jefe_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('delegado_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['jefe_id','delegado_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jefe_delegado');
    }
};

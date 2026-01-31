<?php

// database/migrations/xxxx_xx_xx_create_recinto_jefe_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('recinto_jefe', function (Blueprint $table) {
            $table->id();

            $table->foreignId('recinto_id')
                ->constrained('recintos')
                ->cascadeOnDelete();

            $table->foreignId('jefe_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['recinto_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recinto_jefe');
    }
};

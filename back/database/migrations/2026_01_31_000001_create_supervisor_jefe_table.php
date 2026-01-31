<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('supervisor_jefe', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supervisor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('jefe_id')->constrained('users')->cascadeOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['supervisor_id','jefe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_jefe');
    }
};

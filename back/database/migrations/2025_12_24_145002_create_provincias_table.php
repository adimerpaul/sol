<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('provincias', function (Blueprint $table) {
            $table->id();
            $table->integer('id_original');
            $table->foreignId('departamento_id')->constrained('departamentos');
            $table->string('nombre', 100);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('provincias');
    }
};

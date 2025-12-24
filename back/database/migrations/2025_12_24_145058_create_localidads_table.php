<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('localidades', function (Blueprint $table) {
            $table->id();
            $table->integer('id_original')->nullable();
            $table->foreignId('municipio_id')->constrained('municipios');
            $table->string('nombre', 100);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('localidades');
    }
};

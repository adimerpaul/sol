<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('partidos', function (Blueprint $table) {
            $table->id();
            $table->string('sigla', 50)->unique(); // MAS-IPSP, BST, etc
            $table->string('nombre', 150);         // Movimiento al Socialismo
            $table->string('icono')->nullable();   // fa-solid fa-flag
            $table->string('tipo')->nullable();     // PARTIDO, AGRUPACION, INDIGENA
//            $table->string('alcalde')->nullable();
            $table->string('color', 7)->nullable(); // #FF0000
            $table->integer('orden_municipal')->default(0);
            $table->integer('orden_departamental')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('partidos');
    }
};

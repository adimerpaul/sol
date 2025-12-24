<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('peliculas', function (Blueprint $table) {
            $table->id();

            // referencia TMDB
            $table->unsignedBigInteger('tmdb_id')->unique();

            // campos rápidos (para listar sin parsear json)
            $table->string('title')->nullable();
            $table->string('original_title')->nullable();
            $table->string('original_language', 10)->nullable();
            $table->date('release_date')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('backdrop_path')->nullable();
            $table->decimal('vote_average', 5, 2)->default(0);
            $table->unsignedInteger('vote_count')->default(0);
            $table->decimal('popularity', 10, 4)->default(0);

            // estado publicación
            $table->string('status')->default('No publicado');

            // trailer (url final para usar directo en el front)
            $table->string('trailer_url')->nullable();

            // JSON completo (todo lo que venga de TMDB en 1 sola tabla)
            $table->json('tmdb_data')->nullable();     // detalle /movie/{id}
            $table->json('tmdb_videos')->nullable();   // /movie/{id}/videos

            // opcional: quién guardó
            $table->unsignedBigInteger('user_id')->nullable()->index();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peliculas');
    }
};

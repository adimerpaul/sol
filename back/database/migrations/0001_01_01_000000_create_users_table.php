<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable();


            // ===== NUEVOS CAMPOS PERSONALES =====
            $table->string('nombres', 120);                          // solo nombre(s)
            $table->string('apellido_paterno', 120)->nullable();     // puede ser vacío
            $table->string('apellido_materno', 120);                 // requerido
            $table->string('ci', 30);                      // carnet
            $table->date('fecha_nacimiento');                        // fecha

            // bloque / agrupación / organización
            $table->string('bloque', 180);

            // ===== ARCHIVOS (paths) =====
            $table->string('ci_anverso', 255)->nullable();           // path storage
            $table->string('ci_reverso', 255)->nullable();           // path storage
            $table->string('foto_personal', 255)->nullable();        // path storage

            // ===== LO QUE YA TENÍAS =====
            $table->string('username')->unique();
            $table->string('role')->default('Usuario');              // Administrador, Supervisor, Jefe..., Delegado...
            $table->string('avatar')->default('default.png');        // opcional (tu avatar viejo)
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};

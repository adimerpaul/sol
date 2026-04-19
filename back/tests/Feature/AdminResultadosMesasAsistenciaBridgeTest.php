<?php

use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('shows mobile asistencia flags in admin resultados index and detail', function () {
    seedGeoBase();

    $delegado = makeDelegadoDemo();
    $admin = User::create([
        'name' => 'Admin Demo',
        'nombres' => 'Admin',
        'apellido_paterno' => 'Demo',
        'apellido_materno' => 'Test',
        'ci' => '9999999',
        'fecha_nacimiento' => '1980-01-01',
        'bloque' => 'Jacha',
        'username' => '9999999',
        'role' => 'Administrador',
        'password' => bcrypt('secret'),
    ]);

    DB::table('mesas')->insert([
        'id' => 100,
        'recinto_id' => 10,
        'localidad_id' => 1988,
        'municipio_id' => 191,
        'provincia_id' => 57,
        'departamento_id' => 5,
        'pais_id' => 1,
        'numero_mesa' => 1,
        'delegado_id' => $delegado->id,
        'estado' => 'EN_PROCESO',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Asistencia::create([
        'mesa_id' => 100,
        'delegado_id' => $delegado->id,
        'aviso_antes' => true,
        'aviso_manana' => true,
        'aviso_tarde' => true,
        'hora_apertura_mesa' => '08:10',
        'presente_at' => now(),
    ]);

    Sanctum::actingAs($admin);

    $this->getJson('/api/admin/mesas/resultados-index')
        ->assertOk()
        ->assertJsonPath('data.0.id', 100)
        ->assertJsonPath('data.0.aviso_antes', true)
        ->assertJsonPath('data.0.aviso_manana', true)
        ->assertJsonPath('data.0.aviso_tarde', true)
        ->assertJsonPath('data.0.hora_apertura_mesa', '08:10');

    $this->getJson('/api/admin/mesas/100/resultado')
        ->assertOk()
        ->assertJsonPath('mesa.id', 100)
        ->assertJsonPath('resultado.aviso_antes', true)
        ->assertJsonPath('resultado.aviso_manana', true)
        ->assertJsonPath('resultado.aviso_tarde', true)
        ->assertJsonPath('resultado.hora_apertura_mesa', '08:10');
});

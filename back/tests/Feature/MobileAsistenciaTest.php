<?php

use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function seedGeoBase(): void
{
    $now = now();
    DB::table('paises')->insert(['id' => 1, 'nombre' => 'Bolivia', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('departamentos')->insert(['id' => 5, 'pais_id' => 1, 'nombre' => 'Oruro', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provincias')->insert(['id' => 57, 'departamento_id' => 5, 'nombre' => 'Cercado', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('municipios')->insert(['id' => 191, 'provincia_id' => 57, 'nombre' => 'Oruro', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('localidades')->insert(['id' => 1988, 'municipio_id' => 191, 'nombre' => 'Centro', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('recintos')->insert([
        'id' => 10,
        'localidad_id' => 1988,
        'municipio_id' => 191,
        'provincia_id' => 57,
        'departamento_id' => 5,
        'pais_id' => 1,
        'nombre' => 'Recinto Demo',
        'created_at' => $now,
        'updated_at' => $now,
    ]);
}

function makeDelegadoDemo(): User
{
    return User::create([
        'name' => 'Delegado Demo',
        'nombres' => 'Delegado',
        'apellido_paterno' => 'Demo',
        'apellido_materno' => 'Test',
        'ci' => '1234567',
        'fecha_nacimiento' => '1990-01-01',
        'bloque' => 'Jacha',
        'username' => '1234567',
        'role' => 'Delegado de Mesa',
        'password' => bcrypt('secret'),
    ]);
}

it('stores asistencia in the asistencias table', function () {
    seedGeoBase();
    $delegado = makeDelegadoDemo();

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
        'estado' => 'ASIGNADA',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Sanctum::actingAs($delegado);

    $this->postJson('/api/mobile/asistencia/update', [
        'field' => 'aviso_manana',
        'value' => true,
        'hora_apertura_mesa' => '08:15',
    ])->assertOk();

    $this->assertDatabaseHas('asistencias', [
        'mesa_id' => 100,
        'delegado_id' => $delegado->id,
        'aviso_manana' => 1,
        'hora_apertura_mesa' => '08:15',
        'aviso_manana_by' => $delegado->id,
    ]);
});

it('reads aggregated asistencia state from asistencias', function () {
    seedGeoBase();
    $delegado = makeDelegadoDemo();

    DB::table('mesas')->insert([
        [
            'id' => 100,
            'recinto_id' => 10,
            'localidad_id' => 1988,
            'municipio_id' => 191,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 1,
            'delegado_id' => $delegado->id,
            'estado' => 'ASIGNADA',
            'created_at' => now(),
            'updated_at' => now(),
        ],
        [
            'id' => 101,
            'recinto_id' => 10,
            'localidad_id' => 1988,
            'municipio_id' => 191,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 2,
            'delegado_id' => $delegado->id,
            'estado' => 'ASIGNADA',
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ]);

    Asistencia::create([
        'mesa_id' => 100,
        'delegado_id' => $delegado->id,
        'aviso_manana' => true,
        'aviso_manana_at' => now(),
        'aviso_manana_by' => $delegado->id,
        'hora_apertura_mesa' => '08:10',
    ]);

    Asistencia::create([
        'mesa_id' => 101,
        'delegado_id' => $delegado->id,
        'aviso_manana' => true,
        'aviso_manana_at' => now(),
        'aviso_manana_by' => $delegado->id,
        'hora_apertura_mesa' => '08:10',
    ]);

    Sanctum::actingAs($delegado);

    $this->getJson('/api/mobile/asistencia')
        ->assertOk()
        ->assertJsonPath('mesas', 2)
        ->assertJsonPath('state.aviso_manana', true)
        ->assertJsonPath('state.hora_apertura_mesa', '08:10');
});

it('allows a jefe de recinto to mark delegate attendance for their mesa', function () {
    seedGeoBase();
    $delegado = makeDelegadoDemo();
    $jefe = User::create([
        'name' => 'Jefe Demo',
        'nombres' => 'Jefe',
        'apellido_paterno' => 'Recinto',
        'apellido_materno' => 'Test',
        'ci' => '7654321',
        'fecha_nacimiento' => '1985-01-01',
        'bloque' => 'Jacha',
        'username' => '7654321',
        'role' => 'Jefe de Recinto',
        'password' => bcrypt('secret'),
    ]);

    DB::table('recinto_jefe')->insert([
        'recinto_id' => 10,
        'jefe_id' => $jefe->id,
        'super_jefe' => 0,
        'created_at' => now(),
        'updated_at' => now(),
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
        'estado' => 'ASIGNADA',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Sanctum::actingAs($jefe);

    $this->postJson('/api/mobile/asistencia/delegados/update', [
        'mesa_id' => 100,
        'field' => 'aviso_manana',
        'value' => true,
    ])->assertOk()
        ->assertJsonPath('mesa_id', 100)
        ->assertJsonPath('delegado_id', $delegado->id)
        ->assertJsonPath('registrado_por', $jefe->id);

    $this->assertDatabaseHas('asistencias', [
        'mesa_id' => 100,
        'delegado_id' => $delegado->id,
        'aviso_manana' => 1,
        'aviso_manana_by' => $jefe->id,
    ]);
});

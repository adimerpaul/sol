<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createAdminUser(): User
{
    return User::create([
        'name' => 'Admin Base',
        'nombres' => 'Admin',
        'apellido_paterno' => 'Base',
        'apellido_materno' => 'Sistema',
        'ci' => 'CI-ADMIN-1',
        'fecha_nacimiento' => '1990-01-01',
        'bloque' => 'Jacha',
        'username' => 'admin-base',
        'role' => 'Administrador',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
    ]);
}

it('creates a user without apellido materno', function () {
    $admin = createAdminUser();
    Sanctum::actingAs($admin);

    $response = $this->postJson('/api/users', [
        'nombres' => 'Maria',
        'apellido_paterno' => 'Quispe',
        'ci' => 'CI-USER-1',
        'fecha_nacimiento' => '1995-05-10',
        'bloque' => 'Jacha',
        'role' => 'Supervisor',
    ]);

    $response->assertOk()
        ->assertJsonPath('apellido_materno', null)
        ->assertJsonPath('name', 'Maria Quispe');

    $this->assertDatabaseHas('users', [
        'ci' => 'CI-USER-1',
        'apellido_materno' => null,
        'name' => 'Maria Quispe',
    ]);
});

it('updates a user without apellido materno', function () {
    $admin = createAdminUser();
    $managed = User::create([
        'name' => 'Luis Flores Choque',
        'nombres' => 'Luis',
        'apellido_paterno' => 'Flores',
        'apellido_materno' => 'Choque',
        'ci' => 'CI-USER-2',
        'fecha_nacimiento' => '1992-02-02',
        'bloque' => 'Jacha',
        'username' => 'luis-flores',
        'role' => 'Supervisor',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->putJson("/api/users/{$managed->id}", [
        'nombres' => 'Luis',
        'apellido_paterno' => 'Flores',
        'apellido_materno' => null,
        'ci' => 'CI-USER-2',
        'fecha_nacimiento' => '1992-02-02',
        'bloque' => 'Jacha',
        'role' => 'Supervisor',
        'username' => 'luis-flores',
    ]);

    $response->assertOk()
        ->assertJsonPath('apellido_materno', null)
        ->assertJsonPath('name', 'Luis Flores');

    $this->assertDatabaseHas('users', [
        'id' => $managed->id,
        'apellido_materno' => null,
        'name' => 'Luis Flores',
    ]);
});

it('filters users by recinto_id on index', function () {
    $admin = createAdminUser();

    $paisId = DB::table('paises')->insertGetId([
        'nombre' => 'Bolivia',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $depId = DB::table('departamentos')->insertGetId([
        'nombre' => 'Oruro',
        'pais_id' => $paisId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $provId = DB::table('provincias')->insertGetId([
        'nombre' => 'Cercado',
        'departamento_id' => $depId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $munId = DB::table('municipios')->insertGetId([
        'nombre' => 'Oruro',
        'provincia_id' => $provId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $locId = DB::table('localidades')->insertGetId([
        'nombre' => 'Centro',
        'municipio_id' => $munId,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $recintoA = DB::table('recintos')->insertGetId([
        'nombre' => 'Recinto A',
        'localidad_id' => $locId,
        'municipio_id' => $munId,
        'provincia_id' => $provId,
        'departamento_id' => $depId,
        'pais_id' => $paisId,
        'latitud' => -17.0,
        'longitud' => -67.0,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $recintoB = DB::table('recintos')->insertGetId([
        'nombre' => 'Recinto B',
        'localidad_id' => $locId,
        'municipio_id' => $munId,
        'provincia_id' => $provId,
        'departamento_id' => $depId,
        'pais_id' => $paisId,
        'latitud' => -17.1,
        'longitud' => -67.1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $userInA = User::create([
        'name' => 'Ana Uno',
        'nombres' => 'Ana',
        'apellido_paterno' => 'Uno',
        'apellido_materno' => null,
        'ci' => 'CI-REC-A',
        'fecha_nacimiento' => '1993-03-03',
        'bloque' => 'Jacha',
        'username' => 'ana-uno',
        'role' => 'Supervisor',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
        'recinto_id' => $recintoA,
    ]);

    User::create([
        'name' => 'Beto Dos',
        'nombres' => 'Beto',
        'apellido_paterno' => 'Dos',
        'apellido_materno' => null,
        'ci' => 'CI-REC-B',
        'fecha_nacimiento' => '1994-04-04',
        'bloque' => 'Jacha',
        'username' => 'beto-dos',
        'role' => 'Supervisor',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
        'recinto_id' => $recintoB,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson("/api/users?recinto_id={$recintoA}");

    $response->assertOk()
        ->assertJsonPath('data.0.id', $userInA->id)
        ->assertJsonCount(1, 'data');
});

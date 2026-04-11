<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

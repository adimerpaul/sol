<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createAdminForAsistencia(): User
{
    return User::create([
        'name' => 'Admin Asistencia',
        'nombres' => 'Admin',
        'apellido_paterno' => 'Asistencia',
        'apellido_materno' => 'Sistema',
        'ci' => 'CI-ADMIN-ASIST',
        'fecha_nacimiento' => '1990-01-01',
        'bloque' => 'Jacha',
        'username' => 'admin-asistencia',
        'role' => 'Administrador',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
    ]);
}

it('marks user asistencia from username update and keeps audit fields', function () {
    $admin = createAdminForAsistencia();

    $managed = User::create([
        'name' => 'Maria Perez',
        'nombres' => 'Maria',
        'apellido_paterno' => 'Perez',
        'apellido_materno' => null,
        'ci' => 'CI-ASIST-1',
        'fecha_nacimiento' => '1995-05-10',
        'bloque' => 'Jacha',
        'username' => 'maria-perez',
        'role' => 'Supervisor',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/users/{$managed->id}/username", [
        'username' => 'maria-perez-2',
        'asistencia' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('user.username', 'maria-perez-2')
        ->assertJsonPath('user.asistencia', true);

    $this->assertDatabaseHas('users', [
        'id' => $managed->id,
        'username' => 'maria-perez-2',
        'asistencia' => 1,
        'asistencia_by' => $admin->id,
    ]);

    expect($managed->fresh()->asistencia_at)->not->toBeNull();
});

it('blocks removing asistencia once it was marked', function () {
    $admin = createAdminForAsistencia();

    $managed = User::create([
        'name' => 'Juan Flores',
        'nombres' => 'Juan',
        'apellido_paterno' => 'Flores',
        'apellido_materno' => null,
        'ci' => 'CI-ASIST-2',
        'fecha_nacimiento' => '1994-04-04',
        'bloque' => 'Jacha',
        'username' => 'juan-flores',
        'role' => 'Delegado de Mesa',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
        'asistencia' => true,
        'asistencia_at' => now()->subMinute(),
        'asistencia_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->patchJson("/api/users/{$managed->id}/username", [
        'username' => 'juan-flores',
        'asistencia' => false,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'La asistencia ya fue marcada y no se puede desbloquear');

    $this->assertDatabaseHas('users', [
        'id' => $managed->id,
        'asistencia' => 1,
    ]);
});


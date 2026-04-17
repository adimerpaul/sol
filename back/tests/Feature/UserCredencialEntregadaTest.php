<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function createAdminMesaCredencial(): User
{
    return User::create([
        'name' => 'Admin Credencial',
        'nombres' => 'Admin',
        'apellido_paterno' => 'Credencial',
        'apellido_materno' => 'Sistema',
        'ci' => 'CI-CRED-ADMIN',
        'fecha_nacimiento' => '1990-01-01',
        'bloque' => 'Jacha',
        'username' => 'admin-credencial',
        'role' => 'Administrador',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
    ]);
}

it('marks credencial entregada for a user and returns updated payload', function () {
    $admin = createAdminMesaCredencial();
    $managed = User::create([
        'name' => 'Delegado Credencial',
        'nombres' => 'Delegado',
        'apellido_paterno' => 'Credencial',
        'apellido_materno' => null,
        'ci' => 'CI-CRED-USER-1',
        'fecha_nacimiento' => '1995-05-10',
        'bloque' => 'Jacha',
        'username' => 'delegado-credencial',
        'role' => 'Delegado de Mesa',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/users/{$managed->id}/username", [
        'username' => 'delegado-credencial',
        'credencial_entregada' => true,
    ])->assertOk()
        ->assertJsonPath('message', 'Username actualizado')
        ->assertJsonPath('user.id', $managed->id)
        ->assertJsonPath('user.credencial_entregada', true);

    $this->assertDatabaseHas('users', [
        'id' => $managed->id,
        'credencial_entregada' => 1,
        'credencial_entregada_by' => $admin->id,
    ]);

    expect($managed->fresh()->credencial_entregada_at)->not->toBeNull();
});

it('blocks removing credencial entregada once it was marked', function () {
    $admin = createAdminMesaCredencial();
    $managed = User::create([
        'name' => 'Delegado Credencial Dos',
        'nombres' => 'Delegado',
        'apellido_paterno' => 'Credencial',
        'apellido_materno' => null,
        'ci' => 'CI-CRED-USER-2',
        'fecha_nacimiento' => '1994-04-04',
        'bloque' => 'Jacha',
        'username' => 'delegado-credencial-2',
        'role' => 'Delegado de Mesa',
        'avatar' => 'default.png',
        'password' => bcrypt('secret123'),
        'created_by' => $admin->id,
        'credencial_entregada' => 1,
        'credencial_entregada_at' => now()->subMinute(),
        'credencial_entregada_by' => $admin->id,
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/users/{$managed->id}/username", [
        'username' => 'delegado-credencial-2',
        'credencial_entregada' => false,
    ])->assertStatus(422)
        ->assertJsonPath('message', 'La credencial ya fue marcada como entregada y no se puede desbloquear');

    $this->assertDatabaseHas('users', [
        'id' => $managed->id,
        'credencial_entregada' => 1,
    ]);
});

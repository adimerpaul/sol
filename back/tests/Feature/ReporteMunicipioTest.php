<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function createReporteMunicipioAdmin(): User
{
    $user = User::create([
        'name' => 'Admin Reporte Municipio',
        'nombres' => 'Admin',
        'apellido_paterno' => 'Reporte',
        'apellido_materno' => 'Municipio',
        'ci' => 'RM-ADMIN-1',
        'fecha_nacimiento' => '1990-01-01',
        'bloque' => 'Jacha',
        'username' => 'admin-reporte-municipio',
        'role' => 'Administrador',
        'password' => bcrypt('secret123'),
    ]);

    $permission = Permission::findOrCreate('Reportes por Municipio');
    $user->givePermissionTo($permission);

    return $user;
}

it('returns recinto aggregates for the selected municipio', function () {
    $now = now();

    DB::table('paises')->insert(['id' => 1, 'nombre' => 'Bolivia', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('departamentos')->insert(['id' => 5, 'pais_id' => 1, 'nombre' => 'Oruro', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('provincias')->insert(['id' => 57, 'departamento_id' => 5, 'nombre' => 'Cercado', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('municipios')->insert(['id' => 192, 'provincia_id' => 57, 'nombre' => 'Paria', 'created_at' => $now, 'updated_at' => $now]);
    DB::table('localidades')->insert([
        ['id' => 2006, 'municipio_id' => 192, 'nombre' => 'Paria', 'created_at' => $now, 'updated_at' => $now],
        ['id' => 2007, 'municipio_id' => 192, 'nombre' => 'Soracachi', 'created_at' => $now, 'updated_at' => $now],
    ]);

    DB::table('recintos')->insert([
        [
            'id' => 10,
            'id_original' => 'R-10',
            'localidad_id' => 2006,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'nombre' => 'UE Central Paria',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => 11,
            'id_original' => 'R-11',
            'localidad_id' => 2007,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'nombre' => 'UE Soracachi Norte',
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'id' => 12,
            'id_original' => null,
            'localidad_id' => 2007,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'nombre' => 'Recinto Excluido',
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    DB::table('mesas')->insert([
        [
            'recinto_id' => 10,
            'localidad_id' => 2006,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 1,
            'habilitados' => 300,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'recinto_id' => 10,
            'localidad_id' => 2006,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 2,
            'habilitados' => 250,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'recinto_id' => 11,
            'localidad_id' => 2007,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 1,
            'habilitados' => 180,
            'created_at' => $now,
            'updated_at' => $now,
        ],
        [
            'recinto_id' => 12,
            'localidad_id' => 2007,
            'municipio_id' => 192,
            'provincia_id' => 57,
            'departamento_id' => 5,
            'pais_id' => 1,
            'numero_mesa' => 1,
            'habilitados' => 999,
            'created_at' => $now,
            'updated_at' => $now,
        ],
    ]);

    $admin = createReporteMunicipioAdmin();
    Sanctum::actingAs($admin);

    $this->getJson('/api/reportes-municipio/detalle?departamento_id=5&municipio_id=192')
        ->assertOk()
        ->assertJsonPath('departamento.nombre', 'Oruro')
        ->assertJsonPath('provincia.nombre', 'Cercado')
        ->assertJsonPath('municipio.nombre', 'Paria')
        ->assertJsonPath('totals.recintos', 2)
        ->assertJsonPath('totals.mesas', 3)
        ->assertJsonPath('totals.habilitados', 730)
        ->assertJsonCount(2, 'rows')
        ->assertJsonPath('rows.0.recinto_nombre', 'UE Central Paria')
        ->assertJsonPath('rows.0.total_mesas', 2)
        ->assertJsonPath('rows.0.total_habilitados', 550)
        ->assertJsonPath('rows.1.recinto_nombre', 'UE Soracachi Norte')
        ->assertJsonPath('rows.1.total_mesas', 1)
        ->assertJsonPath('rows.1.total_habilitados', 180);
});

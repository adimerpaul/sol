<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\InsumoProducto;
use App\Models\Producto;
use App\Models\User;
use App\Models\Insumo;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // --- Usuario admin
        $userAdmin = User::create([
            'name'     => 'Admin User',
            'username' => 'admin',
            'role'     => 'Administrador',
            'avatar'   => 'default.png',
            'email'    => '',
            'password' => bcrypt('admin123Admin'), // hash
        ]);
        // =========================
// USUARIOS DE PRUEBA
// =========================

// --- Supervisores
        $supervisores = collect([
            ['name' => 'Carlos Medina',  'username' => 'supervisor1'],
            ['name' => 'Lucía Fernández','username' => 'supervisor2'],
            ['name' => 'Jorge Quispe',   'username' => 'supervisor3'],
        ])->map(fn ($u) => User::create([
            'name'     => $u['name'],
            'username' => $u['username'],
            'role'     => 'Supervisor',
            'avatar'   => 'default.png',
            'email'    => null,
            'password' => bcrypt('123456'),
        ]));

// --- Jefes de Recinto
        $jefes = collect([
            ['name' => 'Ana Rojas',     'username' => 'jefe1'],
            ['name' => 'Mario López',   'username' => 'jefe2'],
            ['name' => 'Patricia Cruz', 'username' => 'jefe3'],
            ['name' => 'Luis Mamani',   'username' => 'jefe4'],
            ['name' => 'Rosa Aguilar',  'username' => 'jefe5'],
            ['name' => 'David Flores',  'username' => 'jefe6'],
        ])->map(fn ($u) => User::create([
            'name'     => $u['name'],
            'username' => $u['username'],
            'role'     => 'Jefe de Recinto',
            'avatar'   => 'default.png',
            'email'    => null,
            'password' => bcrypt('123456'),
        ]));

// --- Delegados de Mesa
        $delegados = collect([
            ['name' => 'Juan Pérez',     'username' => 'delegado1'],
            ['name' => 'María Torres',   'username' => 'delegado2'],
            ['name' => 'José Vargas',    'username' => 'delegado3'],
            ['name' => 'Elena Gómez',    'username' => 'delegado4'],
            ['name' => 'Ricardo Soto',   'username' => 'delegado5'],
            ['name' => 'Carmen Ruiz',    'username' => 'delegado6'],
            ['name' => 'Fernando Lima',  'username' => 'delegado7'],
            ['name' => 'Paola Núñez',    'username' => 'delegado8'],
            ['name' => 'Miguel Ortiz',   'username' => 'delegado9'],
            ['name' => 'Daniela Paredes','username' => 'delegado10'],
        ])->map(fn ($u) => User::create([
            'name'     => $u['name'],
            'username' => $u['username'],
            'role'     => 'Delegado de Mesa',
            'avatar'   => 'default.png',
            'email'    => null,
            'password' => bcrypt('123456'),
        ]));


//        crear 10 usuario falseos
//        User::factory(10)->create();

        // --- Permisos básicos
//        $permisos = [
//            'Usuarios',
//            'Insumos',
//            'Productos',
//            'Clientes',
//            'Ventas',
//            'Compras',
//            'Reportes',
//        ];
//        foreach ($permisos as $permiso) {
//            Permission::firstOrCreate(['name' => $permiso]);
//        }
//        $userAdmin->givePermissionTo(Permission::all());
//        departamentos_202512241454.sql
//localidades_202512241454.sql
//mesas_202512241454.sql
//municipios_202512241454.sql
//paises_202512241454.sql
//provincias_202512241454.sql
//recintos_202512241454.sql
        $url = database_path('seeders/sql/');
        $files = [
            'paises_202512241454.sql',
            'departamentos_202512241454.sql',
            'provincias_202512241454.sql',
            'municipios_202512241454.sql',
            'localidades_202512241454.sql',
            'recintos_202512241454.sql',
            'mesas_202512241524.sql',
            'update_location.sql',
        ];
        foreach ($files as $file) {
            $path = $url . $file;
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        }

        $this->call([
            PartidoSeeder::class,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // =========================
        // USUARIO ADMIN
        // =========================
        $userAdmin = User::create([
            // nuevos campos
            'nombres'          => 'Admin',
            'apellido_paterno' => 'User',
            'apellido_materno' => 'Sistema',
            'ci'               => '10000000',
            'fecha_nacimiento' => '1990-01-01',
            'bloque'           => 'Administración',

            // compatibilidad (si sigues usando name)
            'name'     => 'Admin User Sistema',

            // login
            'username' => 'admin',
            'role'     => 'Administrador',
            'avatar'   => 'default.png',
            'email'    => null,
            'password' => bcrypt('admin123Admin'),

            // archivos (nuevo)
            'ci_anverso'    => null,
            'ci_reverso'    => null,
            'foto_personal' => null,
        ]);

        // =========================
        // USUARIOS DE PRUEBA
        // =========================

        // Helper para crear user con campos nuevos
        $makeUser = function (array $u, string $role, int $ciBase, string $bloque) {
            $nombres = $u['nombres'] ?? $u['name'] ?? 'SinNombre';
            $apPat   = $u['apellido_paterno'] ?? null;
            $apMat   = $u['apellido_materno'] ?? 'SinMaterno';

            $full = trim($nombres . ' ' . ($apPat ?? '') . ' ' . $apMat);

            return User::create([
                // nuevos campos
                'nombres'          => $nombres,
                'apellido_paterno' => $apPat,
                'apellido_materno' => $apMat,
                'ci'               => (string)$ciBase,
                'fecha_nacimiento' => $u['fecha_nacimiento'] ?? '1998-01-01',
                'bloque'           => $bloque,

                // compatibilidad
                'name'     => $full,

                // login
                'username' => $u['username'],
                'role'     => $role,
                'avatar'   => 'default.png',
                'email'    => $u['email'] ?? null,
                'password' => bcrypt($u['password'] ?? '123456'),

                // archivos
                'ci_anverso'    => null,
                'ci_reverso'    => null,
                'foto_personal' => null,
            ]);
        };

        // --- Supervisores
        $supervisoresData = [
            [
                'nombres' => 'Carlos',
                'apellido_paterno' => 'Medina',
                'apellido_materno' => 'Rojas',
                'username' => 'supervisor1',
                'fecha_nacimiento' => '1988-05-10',
            ],
            [
                'nombres' => 'Lucía',
                'apellido_paterno' => 'Fernández',
                'apellido_materno' => 'Choque',
                'username' => 'supervisor2',
                'fecha_nacimiento' => '1992-11-02',
            ],
            [
                'nombres' => 'Jorge',
                'apellido_paterno' => 'Quispe',
                'apellido_materno' => 'Mamani',
                'username' => 'supervisor3',
                'fecha_nacimiento' => '1986-03-18',
            ],
        ];

        $supervisores = collect($supervisoresData)->values()->map(function ($u, $idx) use ($makeUser) {
            return $makeUser($u, 'Supervisor', 20000000 + $idx, 'Supervisoría');
        });

        // --- Jefes de Recinto
        $jefesData = [
            ['nombres' => 'Ana',      'apellido_paterno' => 'Rojas',    'apellido_materno' => 'López',   'username' => 'jefe1', 'fecha_nacimiento' => '1990-01-20'],
            ['nombres' => 'Mario',    'apellido_paterno' => 'López',    'apellido_materno' => 'Quispe',  'username' => 'jefe2', 'fecha_nacimiento' => '1989-07-12'],
            ['nombres' => 'Patricia', 'apellido_paterno' => 'Cruz',     'apellido_materno' => 'Flores',  'username' => 'jefe3', 'fecha_nacimiento' => '1993-09-09'],
            ['nombres' => 'Luis',     'apellido_paterno' => 'Mamani',   'apellido_materno' => 'Rojas',   'username' => 'jefe4', 'fecha_nacimiento' => '1987-12-01'],
            ['nombres' => 'Rosa',     'apellido_paterno' => 'Aguilar',  'apellido_materno' => 'Torres',  'username' => 'jefe5', 'fecha_nacimiento' => '1991-04-22'],
            ['nombres' => 'David',    'apellido_paterno' => 'Flores',   'apellido_materno' => 'Ruiz',    'username' => 'jefe6', 'fecha_nacimiento' => '1985-08-30'],
        ];

        $jefes = collect($jefesData)->values()->map(function ($u, $idx) use ($makeUser) {
            return $makeUser($u, 'Jefe de Recinto', 30000000 + $idx, 'Jefatura de Recinto');
        });

        // --- Delegados de Mesa
        $delegadosData = [
            ['nombres' => 'Juan',     'apellido_paterno' => 'Pérez',    'apellido_materno' => 'Soto',     'username' => 'delegado1',  'fecha_nacimiento' => '1999-02-01'],
            ['nombres' => 'María',    'apellido_paterno' => 'Torres',   'apellido_materno' => 'Lima',     'username' => 'delegado2',  'fecha_nacimiento' => '1998-10-15'],
            ['nombres' => 'José',     'apellido_paterno' => 'Vargas',   'apellido_materno' => 'Mamani',   'username' => 'delegado3',  'fecha_nacimiento' => '1997-06-11'],
            ['nombres' => 'Elena',    'apellido_paterno' => 'Gómez',    'apellido_materno' => 'Paredes',  'username' => 'delegado4',  'fecha_nacimiento' => '2000-01-05'],
            ['nombres' => 'Ricardo',  'apellido_paterno' => 'Soto',     'apellido_materno' => 'Quispe',   'username' => 'delegado5',  'fecha_nacimiento' => '1996-03-27'],
            ['nombres' => 'Carmen',   'apellido_paterno' => 'Ruiz',     'apellido_materno' => 'Aguilar',  'username' => 'delegado6',  'fecha_nacimiento' => '1998-08-08'],
            ['nombres' => 'Fernando', 'apellido_paterno' => 'Lima',     'apellido_materno' => 'Cruz',     'username' => 'delegado7',  'fecha_nacimiento' => '1999-12-12'],
            ['nombres' => 'Paola',    'apellido_paterno' => 'Núñez',    'apellido_materno' => 'Flores',   'username' => 'delegado8',  'fecha_nacimiento' => '2001-05-09'],
            ['nombres' => 'Miguel',   'apellido_paterno' => 'Ortiz',    'apellido_materno' => 'Rojas',    'username' => 'delegado9',  'fecha_nacimiento' => '1997-09-19'],
            ['nombres' => 'Daniela',  'apellido_paterno' => 'Paredes',  'apellido_materno' => 'Torres',   'username' => 'delegado10', 'fecha_nacimiento' => '2000-11-03'],
        ];

        $delegados = collect($delegadosData)->values()->map(function ($u, $idx) use ($makeUser) {
            return $makeUser($u, 'Delegado de Mesa', 40000000 + $idx, 'Delegación');
        });

        // =========================
        // SQL IMPORTS
        // =========================
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
            if (!file_exists($path)) {
                $this->command?->warn("No existe: {$path}");
                continue;
            }
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        }

        // =========================
        // Seeders extra
        // =========================
        $this->call([
            PartidoSeeder::class,
            MenuPermissionsSeeder::class,
        ]);
    }
}

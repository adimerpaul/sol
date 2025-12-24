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
        ];
        foreach ($files as $file) {
            $path = $url . $file;
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Almacen;
use App\Models\InsumoProducto;
use App\Models\Producto;
use App\Models\User;
use App\Models\Insumo;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
        $permisos = [
            'Usuarios',
            'Insumos',
            'Productos',
            'Clientes',
            'Ventas',
            'Compras',
            'Reportes',
        ];
        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso]);
        }
        $userAdmin->givePermissionTo(Permission::all());
    }
}

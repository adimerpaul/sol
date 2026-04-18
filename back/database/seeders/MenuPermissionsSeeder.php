<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MenuPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissionNames = [
            'Dashboard',
            'Recintos',
            'Recintos Mapa',
            'Delegados de Mesa',
            'Partidos',
            'Asignar Recintos',
            'Jerarquia Usuarios',
            'Mapa Asignar Jefes',
            'SuperAdmin Mesas',
            'Reportes por Municipio',
        ];

        $permissions = collect($permissionNames)
            ->map(fn (string $name) => Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]));

        $adminRole = Role::firstOrCreate([
            'name' => 'Administrador',
            'guard_name' => 'web',
        ]);

        $adminRole->syncPermissions($permissions);

        User::query()
            ->where('role', 'Administrador')
            ->get()
            ->each(function (User $user) use ($adminRole, $permissions): void {
                $user->assignRole($adminRole);
                $user->syncPermissions($permissions);
            });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}

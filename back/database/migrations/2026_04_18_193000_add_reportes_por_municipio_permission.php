<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('Reportes por Municipio');

        User::query()
            ->where('role', 'Administrador')
            ->get()
            ->each(function (User $user) use ($permission): void {
                if (!$user->getAllPermissions()->pluck('name')->contains($permission->name)) {
                    $user->givePermissionTo($permission);
                }
            });
    }

    public function down(): void
    {
        Permission::query()->where('name', 'Reportes por Municipio')->delete();
    }
};

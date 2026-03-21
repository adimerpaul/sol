<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $permission = Permission::findOrCreate('Control IA Mesas');

        User::query()
            ->whereIn('role', ['Administrador', 'Supervisor'])
            ->get()
            ->each(function (User $user) use ($permission) {
                if (!$user->getAllPermissions()->pluck('name')->contains($permission->name)) {
                    $user->givePermissionTo($permission);
                }
            });
    }

    public function down(): void
    {
        Permission::query()->where('name', 'Control IA Mesas')->delete();
    }
};

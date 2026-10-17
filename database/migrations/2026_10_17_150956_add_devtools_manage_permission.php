<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Permission modul tool developer (superadmin & user legacy
     * diteruskan otomatis oleh Gate::before).
     */
    public function up(): void
    {
        $permission = Permission::updateOrCreate([
            'name' => 'devtools.manage',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'developer')->first()?->givePermissionTo($permission);
    }

    public function down(): void
    {
        $permission = Permission::where('name', 'devtools.manage')->first();

        if ($permission !== null) {
            $permission->roles()->detach();
            $permission->delete();
        }
    }
};

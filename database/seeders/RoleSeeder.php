<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Role bawaan aplikasi; permission diset by PermissionSeeder
     * (superadmin & system administrator) atau via matrix UI (developer).
     */
    public function run(): void
    {
        foreach (['superadmin', 'developer', 'system administrator'] as $name) {
            Role::firstOrCreate([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }
    }
}

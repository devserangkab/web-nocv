<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Matriks permission per-entitas: <modul>.<aksi>.
     * Idempotent (firstOrCreate) — aman dijalankan ulang; role yang sudah
     * ada tidak pernah ditimpa kecuali superadmin & system administrator
     * yang sengaja disinkronkan penuh.
     *
     * @var array<string, array<int, string>>
     */
    private const MODULES = [
        'locations' => ['view', 'create', 'update', 'delete'],
        'racks' => ['view', 'create', 'update', 'delete'],
        'servers' => ['view', 'create', 'update', 'delete'],
        'proxmox' => ['view', 'create', 'update', 'delete'],
        'vms' => ['view', 'create', 'update', 'delete'],
        'panels' => ['view', 'create', 'update', 'delete'],
        'wafs' => ['view', 'create', 'update', 'delete'],
        'databases' => ['view', 'create', 'update', 'delete'],
        'nas' => ['view', 'create', 'update', 'delete'],
        'applications' => ['view', 'create', 'update', 'delete'],
        'users' => ['view', 'create', 'update', 'delete'],
        'roles' => ['view', 'create', 'update', 'delete'],
        'institutions' => ['view', 'create', 'update', 'delete'],
        'documents' => ['view', 'create', 'delete', 'delete-own'],
        'media' => ['view', 'create', 'delete', 'delete-own'],
        'activities' => ['view', 'create', 'update-own', 'delete-own'],
        'monthly-reports' => ['view', 'create', 'update-own', 'delete-own', 'view-all'],
        'devtools' => ['manage'],
    ];

    public function run(): void
    {
        foreach (self::MODULES as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$module}.{$action}",
                    'guard_name' => 'web',
                ]);
            }
        }

        $all = Permission::query()->get();

        // Superadmin dipercaya penuh (Gate::before juga melewati semua cek) —
        // sync tetap dilakukan agar matrix permission di UI konsisten.
        $superadmin = Role::query()->where('name', 'superadmin')->where('guard_name', 'web')->first();
        $superadmin?->syncPermissions($all);

        // Role manajemen internal ikut dipercaya penuh agar modul baru
        // otomatis tersedia tanpa konfigurasi ulang lewat UI.
        $systemAdmin = Role::query()->where('name', 'system administrator')->where('guard_name', 'web')->first();
        $systemAdmin?->syncPermissions($all);

        // Role lain (mis. developer) sengaja tidak disentuh: permission-nya
        // dikelola lewat matrix UI (halaman Role & Permission).
    }
}

<?php

namespace App\Actions\Management;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class DeleteRole
{
    /**
     * @throws ValidationException bila role superadmin atau masih dipakai user.
     */
    public function __invoke(Role $role): bool
    {
        if ($role->name === 'superadmin') {
            throw ValidationException::withMessages([
                'deletingId' => 'Role superadmin tidak bisa dihapus.',
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Role masih dipakai oleh '.User::role($role->name)->count().' user.',
            ]);
        }

        return $role->delete();
    }
}

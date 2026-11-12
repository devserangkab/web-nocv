<?php

namespace App\Actions\Management;

use App\Models\User;
use App\Support\Authorize;
use Illuminate\Validation\ValidationException;

class DeleteUser
{
    /**
     * @throws ValidationException bila menghapus akun sendiri atau akun superadmin.
     */
    public function __invoke(User $user): bool
    {
        Authorize::permission('users.delete', 'Tidak berwenang menghapus user.');
        if ($user->id === auth()->id()) {
            throw ValidationException::withMessages([
                'deletingId' => 'Tidak bisa menghapus akun sendiri.',
            ]);
        }

        if ($user->hasRole('superadmin')) {
            throw ValidationException::withMessages([
                'deletingId' => 'User superadmin tidak bisa dihapus.',
            ]);
        }

        return $user->delete();
    }
}

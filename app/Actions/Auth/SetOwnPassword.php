<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Ganti password milik user yang sedang login pada alur
 * "wajib ganti password" — memadamkan flag dan mengembalikan User.
 */
class SetOwnPassword
{
    public function __invoke(User $user, string $password): User
    {
        if (Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password baru tidak boleh sama dengan password saat ini.',
            ]);
        }

        $user->password = $password; // cast 'hashed' yang menyimpan
        $user->must_change_password = false;
        $user->save();

        return $user->refresh();
    }
}

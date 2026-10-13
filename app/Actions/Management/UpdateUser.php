<?php

namespace App\Actions\Management;

use App\Models\User;
use Illuminate\Support\Facades\Validator;

class UpdateUser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(User $user, array $data): User
    {
        $data = Validator::make($data, CreateUser::rules($user->id), CreateUser::messages())->validate();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        // Password kosong = biarkan yang lama (field ter-cast 'hashed' bila diisi).
        if (filled($data['password'] ?? null)) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->syncRoles([$data['role']]);

        return $user;
    }
}

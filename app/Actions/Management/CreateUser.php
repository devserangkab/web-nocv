<?php

namespace App\Actions\Management;

use App\Models\User;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class CreateUser
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): User
    {
        Authorize::managementManage();
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $user->syncRoles([$data['role']]);

        return $user;
    }

    /**
     * Saat update (excludeEmail terisi), email unique mengabaikan user sendiri
     * dan password boleh kosong (dipertahankan) bila tidak spesifik.
     *
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        return [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = User::query()
                    ->where('email', $value)
                    ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
                    ->exists();

                if ($taken) {
                    $fail('Email sudah terdaftar.');
                }
            }],
            'password' => array_merge(
                $excludeId === null ? ['required'] : ['nullable'],
                ['min:8', 'same:passwordConfirmation']
            ),
            'role' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Email harus format email yang valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.same' => 'Konfirmasi password tidak cocok.',
            'role.required' => 'Role wajib dipilih.',
        ];
    }
}

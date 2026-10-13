<?php

namespace App\Actions\Management;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UpdateProfile
{
    /**
     * Perbarui profil user sendiri: email unik, password opsional (wajib
     * konfirmasi password saat ini bila diganti), dan avatar (upload baru
     * menggantikan lama; kosong dihapus).
     *
     * @param  array<string, mixed>  $data  name, email, password, passwordConfirmation, current_password
     * @param  TemporaryUploadedFile|null  $avatar  file baru dari upload
     * @param  string  $avatarPath  path avatar sediment (dipertahankan bila kosong dihapus)
     */
    public function __invoke(User $user, array $data, ?TemporaryUploadedFile $avatar = null, string $avatarPath = ''): User
    {
        $data = Validator::make($data, static::rules($user->id), static::messages())->validate();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];

        if (filled($data['password'] ?? null)) {
            if (blank($data['current_password'] ?? null)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password saat ini wajib diisi bila mengganti password.',
                ]);
            }

            if (! Hash::check($data['current_password'], $user->password)) {
                throw ValidationException::withMessages([
                    'current_password' => 'Password saat ini tidak sesuai.',
                ]);
            }

            $payload['password'] = $data['password']; // cast 'hashed' langsung menghash
        }

        if ($avatar instanceof TemporaryUploadedFile) {
            $payload['avatar'] = $avatar->store('avatars', 'public');
        } else {
            $payload['avatar'] = filled($avatarPath) ? $avatarPath : null;
        }

        // Bersihkan file avatar lama bila diganti/dihapus
        if (! blank($user->avatar) && $user->avatar !== $payload['avatar']) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->update($payload);

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        return [
            'name' => ['required', 'max:255'],
            'email' => ['required', 'email', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = User::query()
                    ->where('email', $value)
                    ->where('id', '!=', $excludeId ?? 0)
                    ->exists();

                if ($taken) {
                    $fail('Email sudah dipakai.');
                }
            }],
            'password' => ['nullable', 'min:8', 'same:passwordConfirmation'],
            'current_password' => ['nullable'],
            'avatar' => ['nullable'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Name wajib diisi.',
            'name.max' => 'Name maksimal 255 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Email harus format email yang valid.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.same' => 'Konfirmasi password tidak cocok.',
        ];
    }
}

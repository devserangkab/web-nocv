<?php

namespace App\Actions\Management;

use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateRole
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Role
    {
        $data = Validator::make($data, static::rules(null), static::messages())->validate();

        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions']);

        return $role;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        return [
            'name' => ['required', 'max:255', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = Role::query()
                    ->where('name', $value)
                    ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
                    ->exists();

                if ($taken) {
                    $fail('Nama role sudah dipakai.');
                }
            }],
            'permissions' => ['array'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama role wajib diisi.',
            'name.max' => 'Nama role maksimal 255 karakter.',
        ];
    }
}

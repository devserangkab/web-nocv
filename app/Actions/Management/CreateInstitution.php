<?php

namespace App\Actions\Management;

use App\Models\Institution;
use Illuminate\Support\Facades\Validator;

class CreateInstitution
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Institution
    {
        $data = Validator::make($data, static::rules(null), static::messages())->validate();

        return Institution::create($data);
    }

    /**
     * Cek nama/abbreviation unik dengan pengecualian id saat update.
     *
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeId = null): array
    {
        return [
            'name' => ['required', 'max:255', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = Institution::query()
                    ->where('name', $value)
                    ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
                    ->exists();

                if ($taken) {
                    $fail('Nama institution sudah dipakai.');
                }
            }],
            'abbreviation' => ['required', 'max:50', function (string $attribute, string $value, $fail) use ($excludeId) {
                $taken = Institution::query()
                    ->where('abbreviation', $value)
                    ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
                    ->exists();

                if ($taken) {
                    $fail('Singkatan sudah dipakai.');
                }
            }],
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
            'abbreviation.required' => 'Singkatan wajib diisi.',
            'abbreviation.max' => 'Singkatan maksimal 50 karakter.',
        ];
    }
}

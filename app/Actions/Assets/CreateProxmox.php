<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Proxmox;
use Illuminate\Support\Facades\Validator;

class CreateProxmox
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Proxmox
    {
        $data = Validator::make($data, static::rules(null), static::messages())->validate();

        $proxmox = Proxmox::create($this->normalize($data));

        app(RecordActivity::class)->audit($proxmox, 'Menambahkan proxmox '.$proxmox->name.' ('.$proxmox->proxmox_id.')');

        return $proxmox;
    }

    /**
     * Satu server maksimal satu Proxmox (server optional).
     * Saat update, cek pengecualian terhadap model sendiri.
     *
     * @return array<string, mixed>
     */
    public static function rules(?int $excludeProxmoxId = null): array
    {
        return [
            'name' => ['required', 'max:255'],
            'url' => ['nullable', 'url'],
            'version' => ['nullable', 'max:20'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'assign_server_id' => [
                'nullable',
                'exists:servers,server_id',
                function (string $attribute, string $value, $fail) use ($excludeProxmoxId) {
                    if ($value === '') {
                        return;
                    }

                    $taken = Proxmox::query()
                        ->where('server_id', $value)
                        ->when($excludeProxmoxId !== null, fn ($query) => $query->where('id', '!=', $excludeProxmoxId))
                        ->exists();

                    if ($taken) {
                        $fail('Server sudah memiliki Proxmox.');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama Proxmox wajib diisi.',
            'name.max' => 'Nama Proxmox maksimal 255 karakter.',
            'url.url' => 'URL harus berupa alamat URL yang valid (contoh: https://10.10.1.11:8006).',
            'version.max' => 'Version maksimal 20 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
            'assign_server_id.exists' => 'Server tidak ditemukan.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return [
            'name' => $data['name'],
            'url' => filled($data['url'] ?? null) ? $data['url'] : null,
            'version' => filled($data['version'] ?? null) ? $data['version'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'server_id' => filled($data['assign_server_id'] ?? null) ? $data['assign_server_id'] : null,
        ];
    }
}

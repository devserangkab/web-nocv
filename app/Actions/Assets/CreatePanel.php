<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Enums\PanelType;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreatePanel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Panel
    {
        Authorize::permission('panels.create', 'Tidak berwenang menambah panel.');
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $panel = Panel::create($this->normalize($data));

        app(RecordActivity::class)->audit($panel, 'Menambahkan panel '.$panel->name.' ('.$panel->panel_id.')');

        return $panel;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'host' => ['required', 'regex:/^(vm|srv):[A-Z]+-\d{5}$/', function (string $attribute, string $value, $fail) {
                [$kind, $code] = explode(':', $value, 2);

                $exists = $kind === 'vm'
                    ? Vms::where('vm_id', $code)->exists()
                    : Server::where('server_id', $code)->exists();

                if (! $exists) {
                    $fail($kind === 'vm' ? 'VM tidak ditemukan.' : 'Server tidak ditemukan.');
                }
            }],
            'name' => ['required', 'max:255'],
            'type' => ['required', Rule::enum(PanelType::class)],
            'internet_address' => ['nullable', 'url'],
            'internal_address' => ['nullable', 'url'],
            'version' => ['nullable', 'max:20'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'api_key' => ['nullable', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'host.required' => 'Host wajib dipilih.',
            'host.regex' => 'Host tidak valid.',
            'name.required' => 'Nama panel wajib diisi.',
            'name.max' => 'Nama panel maksimal 255 karakter.',
            'type.required' => 'Jenis panel wajib dipilih.',
            'internet_address.url' => 'Internet address harus berupa URL yang valid (contoh: https://panel.domain:7800).',
            'internal_address.url' => 'Internal address harus berupa URL yang valid (contoh: https://10.10.1.11:7800).',
            'version.max' => 'Version maksimal 20 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
            'api_key.max' => 'API key maksimal 255 karakter.',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        [$kind, $code] = explode(':', $data['host'], 2);

        return [
            'vm_id' => $kind === 'vm' ? $code : null,
            'server_id' => $kind === 'srv' ? $code : null,
            'name' => $data['name'],
            'internet_address' => filled($data['internet_address'] ?? null) ? $data['internet_address'] : null,
            'internal_address' => filled($data['internal_address'] ?? null) ? $data['internal_address'] : null,
            'type' => PanelType::from($data['type']),
            'version' => filled($data['version'] ?? null) ? $data['version'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'api_key' => filled($data['api_key'] ?? null) ? $data['api_key'] : null,
        ];
    }
}

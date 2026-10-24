<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Enums\WafTech;
use App\Models\Server;
use App\Models\Vms;
use App\Models\Waf;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateWaf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Waf
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $waf = Waf::create($this->normalize($data));

        app(RecordActivity::class)->audit($waf, 'Menambahkan WAF '.$waf->name.' ('.$waf->waf_id.')');

        return $waf;
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
            'tech' => ['required', Rule::enum(WafTech::class)],
            'internet_address' => ['nullable', 'url'],
            'internal_address' => ['nullable', 'url'],
            'version' => ['nullable', 'max:20'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
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
            'name.required' => 'Nama WAF wajib diisi.',
            'name.max' => 'Nama WAF maksimal 255 karakter.',
            'tech.required' => 'Teknologi WAF wajib dipilih.',
            'internet_address.url' => 'Internet address harus berupa URL yang valid (contoh: https://waf.domain:9443).',
            'internal_address.url' => 'Internal address harus berupa URL yang valid (contoh: https://10.10.1.11:9443).',
            'version.max' => 'Version maksimal 20 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
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
            'tech' => WafTech::from($data['tech']),
            'version' => filled($data['version'] ?? null) ? $data['version'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
        ];
    }
}

<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Enums\DatabaseTech;
use App\Models\DatabaseInstance;
use App\Models\Panel;
use App\Models\Server;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateDatabaseInstance
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): DatabaseInstance
    {
        Authorize::permission('databases.create', 'Tidak berwenang menambah database.');
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $instance = DatabaseInstance::create($this->normalize($data));

        app(RecordActivity::class)->audit($instance, 'Menambahkan database server '.$instance->name.' ('.$instance->dbi_id.')');

        return $instance;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'host' => ['required', 'regex:/^(vm|srv|pnl):[A-Z]+-\d{5}$/', function (string $attribute, string $value, $fail) {
                [$kind, $code] = explode(':', $value, 2);

                $exists = match ($kind) {
                    'vm' => Vms::where('vm_id', $code)->exists(),
                    'srv' => Server::where('server_id', $code)->exists(),
                    default => Panel::where('panel_id', $code)->exists(),
                };

                if (! $exists) {
                    $fail('Host tidak ditemukan.');
                }
            }],
            'name' => ['required', 'max:255'],
            'tech' => ['required', Rule::enum(DatabaseTech::class)],
            'version' => ['nullable', 'max:20'],
            'internal_address' => ['nullable', 'max:255'],
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
            'name.required' => 'Nama database server wajib diisi.',
            'name.max' => 'Nama database server maksimal 255 karakter.',
            'tech.required' => 'Teknologi database wajib dipilih.',
            'version.max' => 'Version maksimal 20 karakter.',
            'internal_address.max' => 'Internal address maksimal 255 karakter.',
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
            'panel_id' => $kind === 'pnl' ? $code : null,
            'name' => $data['name'],
            'tech' => DatabaseTech::from($data['tech']),
            'version' => filled($data['version'] ?? null) ? $data['version'] : null,
            'internal_address' => filled($data['internal_address'] ?? null) ? $data['internal_address'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
        ];
    }
}

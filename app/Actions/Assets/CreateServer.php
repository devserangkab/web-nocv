<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Enums\ServerStatus;
use App\Models\Rack;
use App\Models\Server;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateServer
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Server
    {
        Authorize::permission('servers.create', 'Tidak berwenang menambah server fisik.');
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $server = Server::create($this->normalize($data));

        app(RecordActivity::class)->audit($server, 'Menambahkan server '.$server->server_name.' ('.$server->server_id.')');

        return $server;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'server_name' => ['required', 'max:255'],
            'rack_id' => ['nullable', 'exists:racks,rack_id'],
            'position' => ['nullable', 'integer', 'min:1'],
            'brand' => ['nullable', 'max:255'],
            'storage_size' => ['nullable', 'integer', 'min:0'],
            'storage' => ['nullable', 'max:255'],
            'memory_size' => ['nullable', 'integer', 'min:0'],
            'memory' => ['nullable', 'max:255'],
            'cpu' => ['nullable', 'max:255'],
            'status' => ['required', Rule::enum(ServerStatus::class)],
            'static_ip' => ['nullable', 'ip'],
            'public_ip' => ['nullable', 'ip'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'os' => ['nullable', 'max:255'],
            'location_id' => ['nullable', 'exists:locations,location_id', 'required_without:rack_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'server_name.required' => 'Nama server wajib diisi.',
            'server_name.max' => 'Nama server maksimal 255 karakter.',
            'rack_id.exists' => 'Rack tidak ditemukan.',
            'position.min' => 'Position harus berupa angka urutan (minimal 1).',
            'brand.max' => 'Brand maksimal 255 karakter.',
            'storage_size.min' => 'Storage harus berupa angka megabyte (MB).',
            'storage.max' => 'Spesifikasi storage maksimal 255 karakter.',
            'memory_size.min' => 'Memory harus berupa angka megabyte (MB).',
            'memory.max' => 'Spesifikasi memory maksimal 255 karakter.',
            'cpu.max' => 'CPU maksimal 255 karakter.',
            'status.in' => 'Status harus ON atau OFF.',
            'static_ip.ip' => 'Static IP harus berupa alamat IP yang valid.',
            'public_ip.ip' => 'Public IP harus berupa alamat IP yang valid.',
            'os.max' => 'OS maksimal 255 karakter.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
            'location_id.exists' => 'Location tidak ditemukan.',
            'location_id.required_without' => 'Location wajib dipilih untuk server tanpa rack.',
        ];
    }

    /**
     * Liakali nilai form (string/kosong) -> bentuk penyimpanan.
     * Location mengikuti rack; wajib eksplisit untuk server tanpa rack.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $rackId = $data['rack_id'] ?? null;

        return [
            'server_name' => $data['server_name'],
            'rack_id' => filled($rackId) ? $rackId : null,
            'location_id' => filled($rackId)
                ? (string) (Rack::with('location')->where('rack_id', $rackId)->first()?->location?->location_id ?? '')
                : ($data['location_id'] ?? null),
            'position' => filled($data['position'] ?? null) ? (int) $data['position'] : null,
            'brand' => filled($data['brand'] ?? null) ? $data['brand'] : null,
            'storage_size' => filled($data['storage_size'] ?? null) ? (int) $data['storage_size'] : null,
            'storage' => filled($data['storage'] ?? null) ? $data['storage'] : null,
            'memory_size' => filled($data['memory_size'] ?? null) ? (int) $data['memory_size'] : null,
            'memory' => filled($data['memory'] ?? null) ? $data['memory'] : null,
            'cpu' => filled($data['cpu'] ?? null) ? $data['cpu'] : null,
            'status' => ServerStatus::from($data['status']),
            'static_ip' => filled($data['static_ip'] ?? null) ? $data['static_ip'] : null,
            'public_ip' => filled($data['public_ip'] ?? null) ? $data['public_ip'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'os' => filled($data['os'] ?? null) ? $data['os'] : null,
        ];
    }
}

<?php

namespace App\Actions\Nas;

use App\Actions\Activities\RecordActivity;
use App\Enums\NasStatus;
use App\Models\Nas;
use App\Models\Rack;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CreateNas
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Nas
    {
        Authorize::permission('nas.create', 'Tidak berwenang menambah NAS.');
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $nas = Nas::create($this->normalize($data));

        app(RecordActivity::class)->audit($nas, 'Menambahkan NAS '.$nas->name.' ('.$nas->nas_id.')');

        return $nas;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'rack_id' => ['nullable', 'exists:racks,rack_id'],
            'location_id' => ['nullable', 'exists:locations,location_id', 'required_without:rack_id'],
            'brand' => ['nullable', 'max:100'],
            'storage_size' => ['nullable', 'integer', 'min:0'],
            'bays' => ['nullable', 'integer', 'min:1'],
            'position' => ['nullable', 'integer', 'min:1'],
            'raid_level' => ['nullable', 'max:40'],
            'os' => ['nullable', 'max:100'],
            'static_ip' => ['nullable', 'ip'],
            'public_ip' => ['nullable', 'ip'],
            'username' => ['nullable', 'max:255'],
            'password' => ['nullable', 'max:255'],
            'status' => ['required', Rule::enum(NasStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama NAS wajib diisi.',
            'name.max' => 'Nama NAS maksimal 255 karakter.',
            'brand.max' => 'Brand maksimal 100 karakter.',
            'storage_size.min' => 'Storage harus berupa angka megabyte (MB).',
            'bays.min' => 'Bays harus berupa angka (minimal 1).',
            'position.min' => 'Position harus berupa angka urutan (minimal 1).',
            'raid_level.max' => 'RAID level maksimal 40 karakter.',
            'os.max' => 'OS maksimal 100 karakter.',
            'static_ip.ip' => 'Static IP harus berupa alamat IP yang valid.',
            'public_ip.ip' => 'Public IP harus berupa alamat IP yang valid.',
            'username.max' => 'Username maksimal 255 karakter.',
            'password.max' => 'Password maksimal 255 karakter.',
            'status.in' => 'Status harus ON atau OFF.',
            'location_id.exists' => 'Location tidak ditemukan.',
            'location_id.required_without' => 'Location wajib dipilih untuk NAS tanpa rack.',
        ];
    }

    /**
     * Normalisasi nilai form; location mengikuti rack bila rack dipilih,
     * wajib eksplisit untuk NAS tanpa rack.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        $rackId = $data['rack_id'] ?? null;

        return [
            'name' => $data['name'],
            'rack_id' => filled($rackId) ? $rackId : null,
            'location_id' => filled($rackId)
                ? (string) (Rack::with('location')->where('rack_id', $rackId)->first()?->location?->location_id ?? '')
                : ($data['location_id'] ?? null),
            'brand' => filled($data['brand'] ?? null) ? $data['brand'] : null,
            'storage_size' => filled($data['storage_size'] ?? null) ? (int) $data['storage_size'] : null,
            'bays' => filled($data['bays'] ?? null) ? (int) $data['bays'] : null,
            'position' => filled($data['position'] ?? null) ? (int) $data['position'] : null,
            'raid_level' => filled($data['raid_level'] ?? null) ? $data['raid_level'] : null,
            'os' => filled($data['os'] ?? null) ? $data['os'] : null,
            'static_ip' => filled($data['static_ip'] ?? null) ? $data['static_ip'] : null,
            'public_ip' => filled($data['public_ip'] ?? null) ? $data['public_ip'] : null,
            'username' => filled($data['username'] ?? null) ? $data['username'] : null,
            'password' => filled($data['password'] ?? null) ? $data['password'] : null,
            'status' => NasStatus::from($data['status']),
        ];
    }
}

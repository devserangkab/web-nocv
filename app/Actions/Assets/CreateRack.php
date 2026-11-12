<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Rack;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class CreateRack
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Rack
    {
        Authorize::permission('racks.create', 'Tidak berwenang menambah rack.');
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $rack = Rack::create($this->normalize($data));

        app(RecordActivity::class)->audit($rack, 'Menambahkan rack '.$rack->name.' ('.$rack->rack_id.')');

        return $rack;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'location_id' => ['required', 'exists:locations,location_id'],
            'name' => ['required', 'max:255'],
            'brand' => ['nullable', 'max:255'],
            'color' => ['nullable', 'string', 'max:50'],
            'units' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'location_id.required' => 'Lokasi rack wajib dipilih.',
            'location_id.exists' => 'Location tidak ditemukan.',
            'name.required' => 'Nama rack wajib diisi.',
            'name.max' => 'Nama rack maksimal 255 karakter.',
            'brand.max' => 'Brand maksimal 255 karakter.',
            'color.max' => 'Warna maksimal 50 karakter.',
            'units.min' => 'Units harus berupa angka (minimal 1).',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        return [
            'location_id' => $data['location_id'],
            'name' => $data['name'],
            'brand' => filled($data['brand'] ?? null) ? $data['brand'] : null,
            'color' => filled($data['color'] ?? null) ? trim((string) $data['color']) : null,
            'units' => filled($data['units'] ?? null) ? (int) $data['units'] : null,
        ];
    }
}

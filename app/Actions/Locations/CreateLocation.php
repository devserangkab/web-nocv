<?php

namespace App\Actions\Locations;

use App\Actions\Activities\RecordActivity;
use App\Models\Location;
use Illuminate\Support\Facades\Validator;

class CreateLocation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(array $data): Location
    {
        $data = Validator::make($data, static::rules(), static::messages())->validate();

        $location = Location::create($this->normalize($data));

        app(RecordActivity::class)->audit($location, 'Menambahkan server room '.$location->name.' ('.$location->location_id.')');

        return $location;
    }

    /**
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'max:255'],
            'address' => ['nullable', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function messages(): array
    {
        return [
            'name.required' => 'Nama server room wajib diisi.',
            'name.max' => 'Nama server room maksimal 255 karakter.',
            'address.max' => 'Alamat maksimal 255 karakter.',
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
            'address' => filled($data['address'] ?? null) ? $data['address'] : null,
        ];
    }
}

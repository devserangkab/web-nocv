<?php

namespace App\Actions\Locations;

use App\Models\Location;
use Illuminate\Support\Facades\Validator;

class UpdateLocation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Location $location, array $data): Location
    {
        $data = Validator::make($data, CreateLocation::rules(), CreateLocation::messages())->validate();

        $location->update(CreateLocation::normalize($data));

        return $location;
    }
}

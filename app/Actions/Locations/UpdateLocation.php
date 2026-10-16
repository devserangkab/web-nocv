<?php

namespace App\Actions\Locations;

use App\Actions\Activities\RecordActivity;
use App\Models\Location;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateLocation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Location $location, array $data): Location
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateLocation::rules(), CreateLocation::messages())->validate();

        $location->update(CreateLocation::normalize($data));

        app(RecordActivity::class)->audit($location, 'Mengubah server room '.$location->name.' ('.$location->location_id.')');

        return $location;
    }
}

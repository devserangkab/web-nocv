<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Rack;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateRack
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Rack $rack, array $data): Rack
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreateRack::rules(), CreateRack::messages())->validate();

        $rack->update(CreateRack::normalize($data));

        app(RecordActivity::class)->audit($rack, 'Mengubah rack '.$rack->name.' ('.$rack->rack_id.')');

        return $rack;
    }
}

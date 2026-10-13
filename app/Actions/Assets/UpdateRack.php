<?php

namespace App\Actions\Assets;

use App\Models\Rack;
use Illuminate\Support\Facades\Validator;

class UpdateRack
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Rack $rack, array $data): Rack
    {
        $data = Validator::make($data, CreateRack::rules(), CreateRack::messages())->validate();

        $rack->update(CreateRack::normalize($data));

        return $rack;
    }
}

<?php

namespace App\Actions\Management;

use App\Models\Institution;
use Illuminate\Support\Facades\Validator;

class UpdateInstitution
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Institution $institution, array $data): Institution
    {
        $data = Validator::make($data, CreateInstitution::rules($institution->id), CreateInstitution::messages())->validate();

        $institution->update($data);

        return $institution;
    }
}

<?php

namespace App\Actions\Management;

use App\Models\Institution;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateInstitution
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Institution $institution, array $data): Institution
    {
        Authorize::permission('institutions.update', 'Tidak berwenang mengubah institution.');
        $data = Validator::make($data, CreateInstitution::rules($institution->id), CreateInstitution::messages())->validate();

        $institution->update($data);

        return $institution;
    }
}

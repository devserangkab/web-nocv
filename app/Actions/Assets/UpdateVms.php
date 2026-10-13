<?php

namespace App\Actions\Assets;

use App\Models\Vms;
use Illuminate\Support\Facades\Validator;

class UpdateVms
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Vms $vm, array $data): Vms
    {
        $data = Validator::make($data, CreateVms::rules(), CreateVms::messages())->validate();

        $normalized = CreateVms::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $vm->update($normalized);

        return $vm;
    }
}

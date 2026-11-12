<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Vms;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateVms
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Vms $vm, array $data): Vms
    {
        Authorize::permission('vms.update', 'Tidak berwenang mengubah virtual machine.');
        $data = Validator::make($data, CreateVms::rules(), CreateVms::messages())->validate();

        $normalized = CreateVms::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $vm->update($normalized);

        app(RecordActivity::class)->audit($vm, 'Mengubah vm '.$vm->name.' ('.$vm->vm_id.')');

        return $vm;
    }
}

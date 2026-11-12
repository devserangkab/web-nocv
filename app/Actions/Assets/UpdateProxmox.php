<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Proxmox;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateProxmox
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Proxmox $proxmox, array $data): Proxmox
    {
        Authorize::permission('proxmox.update', 'Tidak berwenang mengubah host Proxmox.');
        $data = Validator::make($data, CreateProxmox::rules($proxmox->id), CreateProxmox::messages())->validate();

        $normalized = CreateProxmox::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $proxmox->update($normalized);

        app(RecordActivity::class)->audit($proxmox, 'Mengubah proxmox '.$proxmox->name.' ('.$proxmox->proxmox_id.')');

        return $proxmox;
    }
}

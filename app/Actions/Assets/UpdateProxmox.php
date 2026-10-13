<?php

namespace App\Actions\Assets;

use App\Models\Proxmox;
use Illuminate\Support\Facades\Validator;

class UpdateProxmox
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Proxmox $proxmox, array $data): Proxmox
    {
        $data = Validator::make($data, CreateProxmox::rules($proxmox->id), CreateProxmox::messages())->validate();

        $normalized = CreateProxmox::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $proxmox->update($normalized);

        return $proxmox;
    }
}

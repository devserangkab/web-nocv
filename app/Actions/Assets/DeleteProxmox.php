<?php

namespace App\Actions\Assets;

use App\Models\Proxmox;

class DeleteProxmox
{
    public function __invoke(Proxmox $proxmox): bool
    {
        return $proxmox->delete();
    }
}

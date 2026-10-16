<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Proxmox;
use App\Support\Authorize;

class DeleteProxmox
{
    public function __invoke(Proxmox $proxmox): bool
    {
        Authorize::infrastructureManage();
        $deleted = $proxmox->delete();

        app(RecordActivity::class)->audit($proxmox, 'Menghapus proxmox '.($proxmox->name ?? '-').' ('.($proxmox->proxmox_id ?? '-').')');

        return $deleted;
    }
}

<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Vms;

class DeleteVms
{
    public function __invoke(Vms $vm): bool
    {
        $deleted = $vm->delete();

        app(RecordActivity::class)->audit($vm, 'Menghapus vm '.($vm->name ?? '-').'('.($vm->vm_id ?? '-').')');

        return $deleted;
    }
}

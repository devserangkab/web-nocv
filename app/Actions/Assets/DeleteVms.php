<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Vms;
use App\Support\Authorize;

class DeleteVms
{
    public function __invoke(Vms $vm): bool
    {
        Authorize::permission('vms.delete', 'Tidak berwenang menghapus virtual machine.');
        $deleted = $vm->delete();

        app(RecordActivity::class)->audit($vm, 'Menghapus vm '.($vm->name ?? '-').'('.($vm->vm_id ?? '-').')');

        return $deleted;
    }
}

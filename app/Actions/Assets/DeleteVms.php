<?php

namespace App\Actions\Assets;

use App\Models\Vms;

class DeleteVms
{
    public function __invoke(Vms $vm): bool
    {
        return $vm->delete();
    }
}

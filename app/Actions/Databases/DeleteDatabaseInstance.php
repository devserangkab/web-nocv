<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\DatabaseInstance;
use App\Support\Authorize;

class DeleteDatabaseInstance
{
    public function __invoke(DatabaseInstance $instance): bool
    {
        Authorize::infrastructureManage();
        $deleted = $instance->delete();

        // Schema di dalamnya ikut terhapus via FK cascade
        app(RecordActivity::class)->audit($instance, 'Menghapus database server '.($instance->name ?? '-').' ('.($instance->dbi_id ?? '-').')');

        return $deleted;
    }
}

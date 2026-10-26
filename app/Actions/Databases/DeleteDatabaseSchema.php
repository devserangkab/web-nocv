<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\Database;
use App\Support\Authorize;

class DeleteDatabaseSchema
{
    public function __invoke(Database $database): bool
    {
        Authorize::infrastructureManage();

        $deleted = $database->delete();

        // Relasi pemakaian aplikasi ikut terhapus via FK cascade
        app(RecordActivity::class)->audit($database->instance, 'Menghapus database '.($database->name ?? '-').' ('.($database->db_id ?? '-').') dari '.$database->instance->name.' ('.$database->instance->dbi_id.')');

        return $deleted;
    }
}

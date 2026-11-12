<?php

namespace App\Actions\Databases;

use App\Actions\Activities\RecordActivity;
use App\Models\DatabaseUser;
use App\Support\Authorize;

class DeleteDatabaseUser
{
    public function __invoke(DatabaseUser $user): bool
    {
        Authorize::permission('databases.delete', 'Tidak berwenang menghapus user database.');

        $deleted = $user->delete();

        // Pivot aplikasi ↔ schema tetap ada (FK nullOnDelete), kredensialnya saja lepas
        app(RecordActivity::class)->audit($user->instance, 'Menghapus user database '.($user->username ?? '-').' ('.($user->dbu_id ?? '-').') dari '.$user->instance->name.' ('.$user->instance->dbi_id.')');

        return $deleted;
    }
}

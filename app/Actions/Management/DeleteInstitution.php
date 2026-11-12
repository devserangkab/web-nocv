<?php

namespace App\Actions\Management;

use App\Models\Institution;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;

class DeleteInstitution
{
    public function __invoke(Institution $institution): bool
    {
        Authorize::permission('institutions.delete', 'Tidak berwenang menghapus institution.');

        // applications.requested_by akan set null via FK.
        return DB::transaction(fn () => $institution->delete());
    }
}

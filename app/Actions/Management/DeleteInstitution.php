<?php

namespace App\Actions\Management;

use App\Models\Institution;
use Illuminate\Support\Facades\DB;

class DeleteInstitution
{
    public function __invoke(Institution $institution): bool
    {
        // applications.requested_by akan set null via FK.
        return DB::transaction(fn () => $institution->delete());
    }
}

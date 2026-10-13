<?php

namespace App\Actions\Applications;

use App\Models\Application;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteApplication
{
    public function __invoke(Application $application): bool
    {
        return DB::transaction(function () use ($application): bool {
            if (filled($application->icon)) {
                Storage::disk('public')->delete($application->icon);
            }

            // pivot developers ikut terhapus via FK cascade
            return $application->delete();
        });
    }
}

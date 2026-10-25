<?php

namespace App\Actions\Applications;

use App\Actions\Activities\RecordActivity;
use App\Models\Application;
use App\Support\Authorize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteApplication
{
    public function __invoke(Application $application): bool
    {
        Authorize::applicationsManage();

        return DB::transaction(function () use ($application): bool {
            if (filled($application->icon)) {
                Storage::disk('private')->delete($application->icon);
            }

            // pivot developers ikut terhapus via FK cascade
            $deleted = $application->delete();

            app(RecordActivity::class)->audit($application, 'Menghapus application '.$application->name.' ('.$application->application_id.')');

            return $deleted;
        });
    }
}

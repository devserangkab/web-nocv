<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Panel;
use App\Support\Authorize;

class DeletePanel
{
    public function __invoke(Panel $panel): bool
    {
        Authorize::infrastructureManage();
        $deleted = $panel->delete();

        app(RecordActivity::class)->audit($panel, 'Menghapus panel '.($panel->name ?? '-').' ('.($panel->panel_id ?? '-').')');

        return $deleted;
    }
}

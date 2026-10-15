<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Panel;

class DeletePanel
{
    public function __invoke(Panel $panel): bool
    {
        $deleted = $panel->delete();

        app(RecordActivity::class)->audit($panel, 'Menghapus panel '.($panel->name ?? '-').' ('.($panel->panel_id ?? '-').')');

        return $deleted;
    }
}

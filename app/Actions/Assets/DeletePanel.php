<?php

namespace App\Actions\Assets;

use App\Models\Panel;

class DeletePanel
{
    public function __invoke(Panel $panel): bool
    {
        return $panel->delete();
    }
}

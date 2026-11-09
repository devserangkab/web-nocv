<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Panel;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdatePanel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Panel $panel, array $data): Panel
    {
        Authorize::infrastructureManage();
        $data = Validator::make($data, CreatePanel::rules(), CreatePanel::messages())->validate();

        $normalized = CreatePanel::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password', 'api_key'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $panel->update($normalized);

        app(RecordActivity::class)->audit($panel, 'Mengubah panel '.$panel->name.' ('.$panel->panel_id.')');

        return $panel;
    }
}

<?php

namespace App\Actions\Assets;

use App\Models\Panel;
use Illuminate\Support\Facades\Validator;

class UpdatePanel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Panel $panel, array $data): Panel
    {
        $data = Validator::make($data, CreatePanel::rules(), CreatePanel::messages())->validate();

        $normalized = CreatePanel::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $panel->update($normalized);

        return $panel;
    }
}

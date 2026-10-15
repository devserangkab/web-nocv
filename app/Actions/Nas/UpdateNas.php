<?php

namespace App\Actions\Nas;

use App\Actions\Activities\RecordActivity;
use App\Models\Nas;
use Illuminate\Support\Facades\Validator;

class UpdateNas
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Nas $nas, array $data): Nas
    {
        $data = Validator::make($data, CreateNas::rules(), CreateNas::messages())->validate();

        $normalized = CreateNas::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $nas->update($normalized);

        app(RecordActivity::class)->audit($nas, 'Mengubah NAS '.$nas->name.' ('.$nas->nas_id.')');

        return $nas;
    }
}

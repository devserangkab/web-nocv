<?php

namespace App\Actions\Assets;

use App\Actions\Activities\RecordActivity;
use App\Models\Waf;
use App\Support\Authorize;
use Illuminate\Support\Facades\Validator;

class UpdateWaf
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __invoke(Waf $waf, array $data): Waf
    {
        Authorize::permission('wafs.update', 'Tidak berwenang mengubah WAF.');
        $data = Validator::make($data, CreateWaf::rules(), CreateWaf::messages())->validate();

        $normalized = CreateWaf::normalize($data);

        // Kredensial: kosong saat edit = biarkan nilai lama
        foreach (['username', 'password'] as $credential) {
            if (blank($normalized[$credential])) {
                unset($normalized[$credential]);
            }
        }

        $waf->update($normalized);

        app(RecordActivity::class)->audit($waf, 'Mengubah WAF '.$waf->name.' ('.$waf->waf_id.')');

        return $waf;
    }
}
